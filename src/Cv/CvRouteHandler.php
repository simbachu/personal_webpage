<?php

declare(strict_types=1);

namespace App\Cv;

use App\Shared\Http\Route;
use App\Shared\Http\RouteHandler;
use App\Shared\Http\RouteResult;

//! @brief Route handler for the CV page
//!
//! Loads the English CV document and returns flattened template data.
class CvRouteHandler implements RouteHandler
{
    //! @brief Construct the CV route handler
    //! @param loader Loader for cv.json
    public function __construct(
        private readonly CvLoader $loader
    ) {}

    //! @brief Handle the /cv route
    //! @param route The matched route
    //! @param parameters Route parameters (unused)
    //! @return RouteResult Template and CV view data
    public function handle(Route $route, array $parameters = []): RouteResult
    {
        $cv = $this->loader->load('en')->toArray();

        return new RouteResult(
            $route->getTemplate(),
            [
                'cv' => $cv,
                'meta' => [
                    'title' => $cv['name'] . ' — CV',
                    'description' => $cv['summary'],
                ],
            ]
        );
    }
}
