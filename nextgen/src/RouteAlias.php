<?php

declare(strict_types=1);

namespace Tipsy;

/**
 * Route alias for URL redirection and rewriting
 * 
 * Extends Route to provide URL aliasing functionality, allowing one URL pattern
 * to be redirected to another with parameter substitution.
 */
class RouteAlias extends Route
{
    /** @var string Target URL pattern for redirection */
    protected string $_to;
    
    /** @var string Source URL pattern to match */
    protected string $_from;

    /**
     * @param array{tipsy: App, to: string, from: string} $args Configuration array
     */
    public function __construct(array $args = [])
    {
        $this->tipsy($args['tipsy']);

        $this->_to = $args['to'];
        $this->_from = $args['from'];

        // Configure route matching
        $this->_caseSensitive = false;
        $this->_route = preg_replace('/^\/?(.*?)\/?$/i', '\\1', $args['from']);
        $this->_tipsy = $args['tipsy'];
        $this->_method = '*';
        $this->_routeParams = new RouteParams();
    }

    /**
     * Match route and return rewritten URL
     * 
     * @param string $page URL path to match against
     * @return string|false Rewritten URL on match, false otherwise
     */
    public function match(string $page): string|false
    {
        if (!parent::match($page)) {
            return false;
        }

        // Build target URL by substituting parameters
        $targetParts = explode('/', $this->_to);
        $params = $this->_routeParams->properties();

        foreach ($targetParts as $index => $part) {
            foreach ($params as $key => $value) {
                $targetParts[$index] = str_replace(":{$key}", (string) $value, $part);
            }
        }

        return implode('/', $targetParts);
    }
}
