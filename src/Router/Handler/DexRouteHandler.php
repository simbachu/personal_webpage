<?php

declare(strict_types=1);

namespace App\Router\Handler;

use App\Router\RouteHandler;
use App\Router\RouteResult;
use App\Type\Route;
use App\Type\TemplateName;
use App\Type\HttpStatusCode;
use App\Type\MonsterIdentifier;
use App\Presenter\DexPresenter;

//! @brief Route handler for Pokemon dex routes
//!
//! This handler processes dex routes (both /dex and /dex/{id_or_name}) by
//! delegating to the DexPresenter, maintaining the MVP pattern.
class DexRouteHandler implements RouteHandler
{
    private DexPresenter $presenter;

    //! @brief Construct the dex route handler
    //! @param presenter The DexPresenter instance
    public function __construct(DexPresenter $presenter)
    {
        $this->presenter = $presenter;
    }

    //! @brief Handle dex routes
    //! @param route The matched route
    //! @param parameters Route parameters (may contain 'id_or_name')
    //! @return RouteResult The result containing dex data or error
    public function handle(Route $route, array $parameters = []): RouteResult
    {
        // Handle /dex route (no specific Pokemon)
        if (empty($parameters) || !isset($parameters['id_or_name'])) {
            return new RouteResult(
                $route->getTemplate(),
                []
            );
        }

        // Handle /dex/{id_or_name} route
        $idOrName = $parameters['id_or_name'];
        if ($idOrName === '') {
            return new RouteResult(
                TemplateName::NOT_FOUND,
                [
                    'meta' => [
                        'title' => 'Invalid Pokédex Request',
                        'description' => 'No Pokémon specified.',
                    ]
                ],
                HttpStatusCode::BAD_REQUEST
            );
        }

        try {
            // Create MonsterIdentifier and fetch monster data
            $identifier = MonsterIdentifier::fromString($idOrName);
            $monsterData = $this->presenter->fetchMonsterData($identifier);

            // Present the clean data to view
            $presented = $this->presenter->present($monsterData);

            return new RouteResult(
                $presented['template'],
                [
                    'monster' => $presented['monster'],
                    'meta' => [
                        'title' => $presented['monster']['name'] . ' #' . $presented['monster']['id'],
                        'description' => 'Pokédex entry for ' . $presented['monster']['name'],
                    ]
                ]
            );
        } catch (\RuntimeException $e) {
            // Handle Pokemon fetch failure
            return new RouteResult(
                TemplateName::NOT_FOUND,
                [
                    'meta' => [
                        'title' => 'Pokémon Not Found',
                        'description' => $e->getMessage(),
                    ]
                ],
                HttpStatusCode::NOT_FOUND
            );
        }
    }
}
