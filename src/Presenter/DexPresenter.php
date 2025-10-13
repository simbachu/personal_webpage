<?php

declare(strict_types=1);

namespace App\Presenter;

use App\Service\PokeApiService;
use App\Type\MonsterData;

//! @brief Presenter for Pokemon dex detail view with clean separation of concerns
//!
//! This presenter handles the presentation logic for Pokemon dex pages, providing
//! clean MonsterData objects to templates while internally managing Result types
//! from the service layer. The Result type boundary is maintained here.
//!
//! @code
//! // Example usage:
//! $presenter = new DexPresenter($pokeApiService, 300);
//!
//! try {
//!     $monsterData = $presenter->fetchMonsterData('pikachu');
//!     $viewData = $presenter->present($monsterData);
//!     // Use $viewData in template
//! } catch (\RuntimeException $e) {
//!     // Handle service failure
//! }
//! @endcode
class DexPresenter
{
    private PokeApiService $pokeapi; //!< Service for fetching pokemon data
    private int $cacheTtl; //!< Cache TTL in seconds

    //! @brief Construct a new DexPresenter instance
    //! @param pokeapi PokeAPI service for fetching Pokemon data
    //! @param cacheTtl Cache time-to-live in seconds for Pokemon data (defaults to 300)
    public function __construct(PokeApiService $pokeapi, int $cacheTtl = 300)
    {
        $this->pokeapi = $pokeapi;
        $this->cacheTtl = $cacheTtl;
    }

    //! @brief Prepare view model for the dex page from clean MonsterData
    //! @param monsterData The MonsterData object to present to the template
    //! @return array{template:string,monster:array} View data structure for template rendering
    public function present(MonsterData $monsterData): array
    {
        return [
            'template' => 'dex',
            'monster' => $monsterData->toArray(),
        ];
    }

    //! @brief Fetch monster data from service and handle Result type internally
    //! @param id_or_name Pokemon ID (e.g., "25") or name (e.g., "pikachu")
    //! @return MonsterData Clean MonsterData object for presentation
    //! @throws \RuntimeException If the service returns a failure Result
    public function fetchMonsterData(string $id_or_name): MonsterData
    {
        $result = $this->pokeapi->fetchMonster($id_or_name, null, $this->cacheTtl);

        if ($result->isFailure()) {
            throw new \RuntimeException($result->getError());
        }

        return $result->getValue();
    }
}


