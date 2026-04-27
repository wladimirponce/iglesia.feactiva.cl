<?php

declare(strict_types=1);

final class Router
{
    /** @var array<string, array<string, array{handler: callable, middlewares: array<int, string|callable|object>, pattern: string, parameter_names: array<int, string>}>> */
    private array $routes = [];

    /** @param array<int, string|callable|object> $middlewares */
    public function get(string $path, callable $handler, array $middlewares = [], array $options = []): void
    {
        $this->add('GET', $path, $handler, $middlewares, $options);
    }

    /** @param array<int, string|callable|object> $middlewares */
    public function post(string $path, callable $handler, array $middlewares = [], array $options = []): void
    {
        $this->add('POST', $path, $handler, $middlewares, $options);
    }

    /** @param array<int, string|callable|object> $middlewares */
    public function add(string $method, string $path, callable $handler, array $middlewares = [], array $options = []): void
    {
        $normalizedPath = $this->normalizePath($path);
        [$pattern, $parameterNames] = $this->compilePath($normalizedPath);

        $this->routes[strtoupper($method)][$normalizedPath] = [
            'handler' => $handler,
            'middlewares' => $this->withRoutePolicyMiddlewares($middlewares, $options),
            'pattern' => $pattern,
            'parameter_names' => $parameterNames,
        ];
    }

    public function dispatch(string $method, string $path): void
    {
        AuthContext::reset();

        $method = strtoupper($method);
        $path = $this->normalizePath($path);

        $match = $this->matchRoute($method, $path);

        if ($match !== null) {
            $route = $match['route'];
            $this->runRoute($route['handler'], $route['middlewares'], $match['parameters']);
            return;
        }

        foreach ($this->routes as $registeredMethod => $routes) {
            if ($registeredMethod !== $method && $this->matchRoute($registeredMethod, $path) !== null) {
                Response::error('METHOD_NOT_ALLOWED', 'Metodo HTTP no permitido.', [], 405);
                return;
            }
        }

        Response::error('NOT_FOUND', 'Ruta no encontrada.', [], 404);
    }

    /** @param array<int, string|callable|object> $middlewares */
    private function runRoute(callable $handler, array $middlewares, array $parameters = []): void
    {
        $next = function () use ($handler, $parameters): void {
            $handler(...$parameters);
        };

        foreach (array_reverse($middlewares) as $middleware) {
            $next = function () use ($middleware, $next): void {
                $this->runMiddleware($middleware, $next);
            };
        }

        $next();
    }

    private function runMiddleware(string|callable|object $middleware, callable $next): void
    {
        if (is_string($middleware)) {
            if (!class_exists($middleware)) {
                Response::error('MIDDLEWARE_NOT_FOUND', 'Middleware no encontrado.', [], 500);
                return;
            }

            $middleware = new $middleware();
        }

        if (is_object($middleware) && method_exists($middleware, 'handle')) {
            $middleware->handle($next);
            return;
        }

        if (is_callable($middleware)) {
            $middleware($next);
            return;
        }

        Response::error('INVALID_MIDDLEWARE', 'Middleware invalido.', [], 500);
    }

    private function normalizePath(string $path): string
    {
        $path = '/' . trim($path, '/');
        return $path === '/' ? '/' : rtrim($path, '/');
    }

    private function compilePath(string $path): array
    {
        $parameterNames = [];
        $pattern = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', static function (array $matches) use (&$parameterNames): string {
            $parameterNames[] = $matches[1];
            return '([^/]+)';
        }, $path);

        return ['#^' . $pattern . '$#', $parameterNames];
    }

    private function matchRoute(string $method, string $path): ?array
    {
        foreach ($this->routes[$method] ?? [] as $route) {
            if (!preg_match($route['pattern'], $path, $matches)) {
                continue;
            }

            array_shift($matches);

            return [
                'route' => $route,
                'parameters' => $matches,
            ];
        }

        return null;
    }

    /** @param array<int, string|callable|object> $middlewares */
    private function withRoutePolicyMiddlewares(array $middlewares, array $options): array
    {
        if (isset($options['module']) && is_string($options['module']) && $options['module'] !== '') {
            $middlewares[] = new ModuleMiddleware($options['module']);
        }

        if (isset($options['permission']) && is_string($options['permission']) && $options['permission'] !== '') {
            $middlewares[] = new PermissionMiddleware($options['permission']);
        }

        return $middlewares;
    }
}
