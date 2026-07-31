<?php

declare(strict_types=1);

namespace App\Cv;

use App\Shared\Http\Route;
use App\Shared\Http\RouteHandler;
use App\Shared\Http\RouteResult;

//! @brief Route handler for the CV page
//!
//! Loads the selected CV document and returns flattened template data.
class CvRouteHandler implements RouteHandler
{
    //! @brief Construct the CV route handler
    //! @param loader Loader for cv.json
    //! @param language Selected CV language
    public function __construct(
        private readonly CvLoader $loader,
        private readonly string $language = 'en'
    ) {}

    //! @brief Handle the /cv route
    //! @param route The matched route
    //! @param parameters Route parameters (unused)
    //! @return RouteResult Template and CV view data
    public function handle(Route $route, array $parameters = []): RouteResult
    {
        $cv = $this->loader->load($this->language)->toArray();

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
