<?php

declare(strict_types=1);

namespace App\Presenter;

use App\Service\PokeApiService;

//! @brief Presenter for the Pokédex detail view
class DexPresenter
{
    private PokeApiService $pokeapi; //!< Service for fetching pokemon data
    private int $cacheTtl; //!< Cache TTL in seconds

    public function __construct(PokeApiService $pokeapi, int $cacheTtl = 300)
    {
        $this->pokeapi = $pokeapi;
        $this->cacheTtl = $cacheTtl;
    }

    //! @brief Prepare view model for the dex page
    //! @param id_or_name Monster id or name
    //! @return array{template:string,monster:array}
    public function present(string $id_or_name): array
    {
        $monster = $this->pokeapi->fetchMonster($id_or_name, null, $this->cacheTtl);
        return [
            'template' => 'dex',
            'monster' => $monster,
        ];
    }
}


