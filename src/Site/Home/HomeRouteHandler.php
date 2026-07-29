<?php

declare(strict_types=1);

namespace App\Site\Home;

use App\Shared\Http\RouteHandler;
use App\Shared\Http\RouteResult;
use App\Shared\Http\Route;
use App\Site\Home\HomePresenter;

//! @brief Route handler for the home page
//!
//! This handler processes the home route by delegating to the HomePresenter,
//! maintaining the MVP pattern separation between routing and presentation logic.
class HomeRouteHandler implements RouteHandler
{
    private HomePresenter $presenter;

    //! @brief Construct the home route handler
    //! @param presenter The HomePresenter instance
    public function __construct(HomePresenter $presenter)
    {
        $this->presenter = $presenter;
    }

    //! @brief Handle the home route
    //! @param route The matched route (unused for home route)
    //! @param parameters Route parameters (unused for home route)
    //! @return RouteResult The result containing home page data
    public function handle(Route $route, array $parameters = []): RouteResult
    {
        $homeData = $this->presenter->present();

        return new RouteResult(
            $route->getTemplate(),
            $homeData
        );
    }
}
