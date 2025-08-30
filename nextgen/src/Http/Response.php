<?php

declare(strict_types=1);

namespace Tipsy\Http;

/**
 * HTTP Response wrapper with status detection and callback support
 * 
 * Provides a fluent interface for handling HTTP responses with automatic
 * JSON parsing and success/error status detection.
 */
class Response
{
    private bool $_status;
    private int $_httpStatus;
    private string $_errors;
    private mixed $_body;
    private array $_headers;

    /**
     * @param string $errors cURL errors, if any
     * @param string $body Response body
     * @param array<string, string> $headers Response headers
     */
    public function __construct(string $errors, string $body, array $headers)
    {
        $this->_body = $body;
        $this->_headers = $headers;
        $this->_errors = $errors;
        
        // Extract HTTP status code
        $httpCodeParts = explode(' ', $headers['http_code'] ?? '200 OK');
        $this->_httpStatus = (int) ($httpCodeParts[1] ?? 200);

        // Determine overall status
        $this->_status = !$this->_errors && 
                        $this->_httpStatus >= 200 && 
                        $this->_httpStatus < 400;

        // Auto-parse JSON responses
        $contentType = $headers['Content-Type'] ?? '';
        $type = explode(';', $contentType)[0];

        if ($type === 'application/json' && $body) {
            try {
                $this->_body = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                // Keep original body if JSON parsing fails
                $this->_body = $body;
            }
        }
    }

    /**
     * Get response headers
     * 
     * @return array<string, string>
     */
    public function headers(): array
    {
        return $this->_headers;
    }

    /**
     * Get response body (parsed if JSON)
     */
    public function body(): mixed
    {
        return $this->_body;
    }

    /**
     * Get HTTP status code
     */
    public function status(): int
    {
        return $this->_httpStatus;
    }

    /**
     * Check if response was successful
     */
    public function isSuccessful(): bool
    {
        return $this->_status;
    }

    /**
     * Get error information
     */
    public function errors(): string
    {
        return $this->_errors;
    }

    /**
     * Execute callback for any response (success or error)
     */
    public function complete(callable $callback): self
    {
        $callback($this->body(), $this->headers());
        return $this;
    }

    /**
     * Execute callback only on error responses
     */
    public function error(callable $callback): self
    {
        if (!$this->_status) {
            $this->complete($callback);
        }
        return $this;
    }

    /**
     * Execute callback only on successful responses
     */
    public function success(callable $callback): self
    {
        if ($this->_status) {
            $this->complete($callback);
        }
        return $this;
    }
}
