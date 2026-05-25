<?php

declare(strict_types=1);

class Router
{
    private array $_routes = [];

    public function add(string $method, string $pattern, callable $handler): void
    {
        $regex = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $pattern);
        $this->_routes[] = [
            'method'  => strtoupper($method),
            'regex'   => '#^' . $regex . '$#',
            'handler' => $handler,
        ];
    }

    public function get(string $pattern, callable $handler): void  { $this->add('GET',    $pattern, $handler); }
    public function post(string $pattern, callable $handler): void { $this->add('POST',   $pattern, $handler); }
    public function put(string $pattern, callable $handler): void  { $this->add('PUT',    $pattern, $handler); }
    public function delete(string $pattern, callable $handler): void { $this->add('DELETE', $pattern, $handler); }

    public function dispatch(Request $request): void
    {
        if ($request->method === 'OPTIONS') {
            http_response_code(204);
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization');
            exit;
        }

        foreach ($this->_routes as $route) {
            if ($route['method'] !== $request->method) {
                continue;
            }
            if (!preg_match($route['regex'], $request->uri, $m)) {
                continue;
            }
            $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
            ($route['handler'])($request, $params);
            return;
        }

        Response::notFound("Endpoint '{$request->uri}' not found.");
    }
}
