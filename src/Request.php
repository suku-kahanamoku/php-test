<?php

declare(strict_types=1);

class Request
{
    public readonly string $method;
    public readonly string $uri;
    public readonly array  $query;
    public readonly array  $body;
    public readonly array  $headers;

    public function __construct()
    {
        $this->method  = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $this->uri     = $this->_parsePath();
        $this->query   = $_GET;
        $this->body    = $this->_parseBody();
        $this->headers = $this->_parseHeaders();
    }

    private function _parsePath(): string
    {
        $uri       = $_SERVER['REQUEST_URI'] ?? '/';
        $path      = parse_url($uri, PHP_URL_PATH) ?: '/';
        $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');

        if ($scriptDir !== '' && str_starts_with($path, $scriptDir)) {
            $path = substr($path, strlen($scriptDir));
        }

        return rtrim($path, '/') ?: '/';
    }

    private function _parseBody(): array
    {
        $ct = $_SERVER['CONTENT_TYPE'] ?? '';

        if (str_contains($ct, 'application/json')) {
            $raw     = file_get_contents('php://input');
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? $decoded : [];
        }

        return $_POST;
    }

    private function _parseHeaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name                       = str_replace('_', '-', substr($key, 5));
                $headers[strtolower($name)] = $value;
            }
        }
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['content-type'] = $_SERVER['CONTENT_TYPE'];
        }
        return $headers;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function header(string $name, mixed $default = null): mixed
    {
        return $this->headers[strtolower($name)] ?? $default;
    }

    /** Vrati Bearer token z Authorization hlavicky, nebo null. */
    public function bearerToken(): ?string
    {
        $auth = $this->header('authorization', '');
        if (str_starts_with($auth, 'Bearer ')) {
            return substr($auth, 7);
        }
        return null;
    }
}
