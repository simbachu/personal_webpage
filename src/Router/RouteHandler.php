<?php

declare(strict_types=1);

namespace App\Router;

use App\Type\Route;
use App\Type\TemplateName;

//! @brief Interface for route handlers that process route-specific logic
//!
//! Route handlers encapsulate the business logic for specific routes,
//! following the MVP pattern by separating concerns between routing,
//! presentation, and business logic.
//!
//! @code
//! // Example usage:
//! class HomeRouteHandler implements RouteHandler {
//!     public function handle(Route $route, array $parameters = []): RouteResult {
//!         // Process home route logic
//!         return new RouteResult($route->getTemplate(), ['data' => 'value']);
//!     }
//! }
//! @endcode
interface RouteHandler
{
    //! @brief Handle a route and return the result
    //! @param route The matched route
    //! @param parameters Extracted route parameters
    //! @return RouteResult The result containing template and data
    public function handle(Route $route, array $parameters = []): RouteResult;
}
