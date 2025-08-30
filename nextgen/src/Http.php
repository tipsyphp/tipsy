<?php

declare(strict_types=1);

namespace Tipsy;

/**
 * HTTP client for making requests with support for various HTTP methods
 * 
 * Provides a simple interface for HTTP requests with automatic method handling,
 * JSON/form data support, and response parsing.
 */
class Http
{
    /**
     * Handle dynamic HTTP method calls (GET, POST, PUT, DELETE, etc.)
     */
    public function __call(string $name, array $args): Http\Response
    {
        // Add method to arguments based on call structure
        if (is_array($args[0] ?? null)) {
            $args[0]['method'] = $name;
        } elseif (count($args) === 3) {
            $args[2]['method'] = $name;
        } else {
            $args[2] = ['method' => $name];
        }
        
        return $this->request(...$args);
    }

    /**
     * Make HTTP request with flexible parameter handling
     * 
     * Supports multiple call signatures:
     * - request($url, $data, $options)
     * - request($options)
     */
    public function request(string|array $urlOrOptions = '', mixed $data = null, array $options = []): Http\Response
    {
        // Parse flexible arguments
        if (is_array($urlOrOptions)) {
            $options = $urlOrOptions;
            $url = $options['url'] ?? '';
            $data = $options['data'] ?? null;
        } else {
            $url = $urlOrOptions;
            if ($data !== null && !isset($options['data'])) {
                $options['data'] = $data;
            }
        }

        // Extract configuration with defaults
        $method = strtoupper($options['method'] ?? 'GET');
        $dataType = strtolower($options['type'] ?? 'form');
        $requestData = $options['data'] ?? $data;

        // Prepare request data based on type and method
        $requestData = $this->prepareRequestData($requestData, $dataType, $method);

        // Initialize cURL
        $ch = $this->initializeCurl($url, $method, $requestData, $dataType);

        // Execute request
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            return new Http\Response($error ?: 'Unknown cURL error', '', []);
        }

        // Parse response
        [$headers, $body] = $this->parseResponse($response);

        return new Http\Response($error, $body, $headers);
    }

    /**
     * Prepare request data based on content type and HTTP method
     */
    private function prepareRequestData(mixed $data, string $dataType, string $method): string
    {
        if (!$data) {
            return '';
        }

        return match ([$dataType, $method]) {
            ['json', 'POST'] => json_encode($data, JSON_THROW_ON_ERROR),
            default => is_array($data) ? http_build_query($data) : (string) $data
        };
    }

    /**
     * Initialize cURL handle with proper options
     */
    private function initializeCurl(string $url, string $method, string $data, string $dataType): \CurlHandle
    {
        // Adjust URL for GET requests with data
        if ($method === 'GET' && $data) {
            $url .= (str_contains($url, '?') ? '&' : '?') . $data;
        }

        $ch = curl_init($url);

        // Basic cURL options
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_AUTOREFERER => true,
            CURLOPT_HEADER => true,
            CURLOPT_TIMEOUT => 30,
        ]);

        // Set request body for non-GET methods
        if ($method !== 'GET' && $data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        // Set headers
        $headers = $this->buildHeaders($method, $dataType, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        return $ch;
    }

    /**
     * Build HTTP headers based on request configuration
     * 
     * @return string[]
     */
    private function buildHeaders(string $method, string $dataType, string $data): array
    {
        $headers = ['User-Agent: PHP/Tipsy/Http'];

        if ($method === 'POST' && $dataType === 'json') {
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'Content-Length: ' . strlen($data);
        }

        return $headers;
    }

    /**
     * Parse cURL response into headers and body
     * 
     * @return array{0: array<string, string>, 1: string}
     */
    private function parseResponse(string $response): array
    {
        // Handle multiple HTTP responses (redirects)
        while (str_starts_with($response, 'HTTP')) {
            $separator = str_contains($response, "\r\n\r\n") ? "\r\n\r\n" : "\n\n";
            $parts = explode($separator, $response, 2);
            
            if (count($parts) < 2) {
                break;
            }
            
            [$headerString, $response] = $parts;
        }

        // Parse headers
        $headers = [];
        $headerLines = explode("\r\n", $headerString ?? '');
        
        foreach ($headerLines as $i => $line) {
            if ($i === 0) {
                $headers['http_code'] = $line;
            } elseif (str_contains($line, ': ')) {
                [$key, $value] = explode(': ', $line, 2);
                $headers[$key] = $value;
            }
        }

        return [$headers, $response];
    }
}
