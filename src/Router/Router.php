<?php

declare(strict_types=1);

namespace App\Router;

use App\Type\Route;
use App\Type\TemplateName;
use App\Type\HttpStatusCode;

//! @brief Router class that handles HTTP request routing following MVP pattern
//!
//! This router provides a clean separation between routing logic and business logic,
//! allowing for easy testing and maintenance. It uses a route table approach with
//! support for both static and dynamic routes.
//!
//! @code
//! // Example usage:
//! $router = new Router();
//! $router->addRoute(new Route('/', TemplateName::HOME, ['title' => 'Home']));
//! $router->addRoute(new Route('/dex', TemplateName::DEX, [], ['handler' => 'dex']));
//!
//! $result = $router->route('/');
//! // $result contains template and data for home page
//! @endcode
class Router
{
    /** @var Route[] Array of registered routes */
    private array $routes = [];

    /** @var array<string, RouteHandler> Map of handler names to handler instances */
    private array $handlers = [];

    //! @brief Add a route to the router
    //! @param route The route to add
    public function addRoute(Route $route): void
    {
        $this->routes[] = $route;
    }

    //! @brief Register a route handler
    //! @param name The handler name
    //! @param handler The handler instance
    public function registerHandler(string $name, RouteHandler $handler): void
    {
        $this->handlers[$name] = $handler;
    }

    //! @brief Route a request path to the appropriate handler
    //! @param path The request path to route
    //! @return RouteResult The result containing template and data
    public function route(string $path): RouteResult
    {
        $normalizedPath = $this->normalizePath($path);

        // Find matching route
        foreach ($this->routes as $route) {
            if ($route->matches($normalizedPath)) {
                return $this->handleRoute($route, $route->extractParameters($normalizedPath));
            }
        }

        // No route found - return 404
        return $this->createNotFoundResult();
    }

    //! @brief Handle a matched route
    //! @param route The matched route
    //! @param parameters Extracted route parameters
    //! @return RouteResult The result from handling the route
    private function handleRoute(Route $route, array $parameters): RouteResult
    {
        $handlerName = $route->getOption('handler');

        // If route has a specific handler, use it
        if ($handlerName && isset($this->handlers[$handlerName])) {
            $result = $this->handlers[$handlerName]->handle($route, $parameters);

            // Merge route metadata with handler result, but don't overwrite existing meta
            $handlerData = $result->getData();
            if (!isset($handlerData['meta'])) {
                $result = $result->withData([
                    'meta' => $route->getMeta()
                ]);
            }

            return $result;
        }

        // Default handling - just return the template with metadata
        return new RouteResult(
            $route->getTemplate(),
            ['meta' => $route->getMeta()]
        );
    }

    //! @brief Create a 404 Not Found result
    //! @return RouteResult The 404 result
    private function createNotFoundResult(): RouteResult
    {
        return new RouteResult(
            TemplateName::NOT_FOUND,
            [
                'meta' => [
                    'title' => 'Page Not Found',
                    'description' => 'The page you are looking for does not exist.',
                ]
            ],
            HttpStatusCode::NOT_FOUND
        );
    }

    //! @brief Normalize a path by removing trailing slashes (except root)
    //! @param path The path to normalize
    //! @return string The normalized path
    private function normalizePath(string $path): string
    {
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }
        return $path;
    }

    //! @brief Get all registered routes (for testing/debugging)
    //! @return Route[] Array of all registered routes
    public function getRoutes(): array
    {
        return $this->routes;
    }

    //! @brief Get all registered handlers (for testing/debugging)
    //! @return array<string, RouteHandler> Map of handler names to handlers
    public function getHandlers(): array
    {
        return $this->handlers;
    }

    //! @brief Clear all routes and handlers (for testing)
    public function clear(): void
    {
        $this->routes = [];
        $this->handlers = [];
    }
}
