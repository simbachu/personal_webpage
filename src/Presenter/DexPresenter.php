<?php

declare(strict_types=1);

namespace App\Presenter;

use App\Service\PokeApiService;

//! @brief Presenter for the Pokédex detail view
class DexPresenter
{
    private PokeApiService $pokeapi; //!< Service for fetching pokemon data

    public function __construct(PokeApiService $pokeapi)
    {
        $this->pokeapi = $pokeapi;
    }

    //! @brief Prepare view model for the dex page
    //! @param id_or_name Pokemon id or name
    //! @return array{template:string,monster:array}
    public function present(string $id_or_name): array
    {
        $monster = $this->pokeapi->fetchPokemon($id_or_name);
        return [
            'template' => 'dex',
            'monster' => $monster,
        ];
    }
}


