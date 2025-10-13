<?php

declare(strict_types=1);

namespace Tests\TestData;

//! @brief Local test data for Pokemon to avoid API calls during testing
class PokemonTestData
{
    //! @brief Get test data for Bulbasaur (ID: 001) - dual type Pokemon
    //! @return array{id:int,name:string,image:string,type1:string,type2:string}
    public static function getBulbasaur(): array
    {
        return [
            'id' => 1,
            'name' => 'Bulbasaur',
            'image' => 'https://img.example/bulbasaur.png',
            'type1' => 'grass',
            'type2' => 'poison',
        ];
    }

    //! @brief Get test data for Charmander (ID: 004) - single type Pokemon
    //! @return array{id:int,name:string,image:string,type1:string}
    public static function getCharmander(): array
    {
        return [
            'id' => 4,
            'name' => 'Charmander',
            'image' => 'https://img.example/charmander.png',
            'type1' => 'fire',
        ];
    }

    //! @brief Get raw JSON data for Bulbasaur (ID: 001) as returned by PokeAPI
    //! @return string JSON encoded Pokemon data
    public static function getBulbasaurJson(): string
    {
        return json_encode([
            'id' => 1,
            'name' => 'bulbasaur',
            'types' => [
                ['slot' => 1, 'type' => ['name' => 'grass']],
                ['slot' => 2, 'type' => ['name' => 'poison']],
            ],
            'sprites' => [
                'other' => [
                    'official-artwork' => [
                        'front_default' => 'https://img.example/bulbasaur.png'
                    ]
                ]
            ]
        ]);
    }

    //! @brief Get raw JSON data for Charmander (ID: 004) as returned by PokeAPI
    //! @return string JSON encoded Pokemon data
    public static function getCharmanderJson(): string
    {
        return json_encode([
            'id' => 4,
            'name' => 'charmander',
            'types' => [
                ['slot' => 1, 'type' => ['name' => 'fire']],
            ],
            'sprites' => [
                'other' => [
                    'official-artwork' => [
                        'front_default' => 'https://img.example/charmander.png'
                    ]
                ]
            ]
        ]);
    }

    //! @brief Get test data for a Pokemon with no types (edge case)
    //! @return array{id:int,name:string,image:string,type1:string}
    public static function getTypelessPokemon(): array
    {
        return [
            'id' => 999,
            'name' => 'Unknown',
            'image' => 'https://img.example/unknown.png',
            'type1' => '',
        ];
    }

    //! @brief Get raw JSON data for a Pokemon with no types (edge case)
    //! @return string JSON encoded Pokemon data
    public static function getTypelessPokemonJson(): string
    {
        return json_encode([
            'id' => 999,
            'name' => 'unknown',
            'sprites' => [
                'other' => [
                    'official-artwork' => [
                        'front_default' => 'https://img.example/unknown.png'
                    ]
                ]
            ]
        ]);
    }

    //! @brief Get test data for Ditto (ID: 132) - no evolution
    //! @return array{id:int,name:string,image:string,type1:string}
    public static function getDitto(): array
    {
        return [
            'id' => 132,
            'name' => 'Ditto',
            'image' => 'https://img.example/ditto.png',
            'type1' => 'normal',
        ];
    }

    //! @brief Get test data for Ivysaur (ID: 002) - middle evolution
    //! @return array{id:int,name:string,image:string,type1:string,type2:string}
    public static function getIvysaur(): array
    {
        return [
            'id' => 2,
            'name' => 'Ivysaur',
            'image' => 'https://img.example/ivysaur.png',
            'type1' => 'grass',
            'type2' => 'poison',
        ];
    }

    //! @brief Get test data for Venusaur (ID: 003) - final evolution
    //! @return array{id:int,name:string,image:string,type1:string,type2:string}
    public static function getVenusaur(): array
    {
        return [
            'id' => 3,
            'name' => 'Venusaur',
            'image' => 'https://img.example/venusaur.png',
            'type1' => 'grass',
            'type2' => 'poison',
        ];
    }

    //! @brief Get test data for Bulbasaur with evolution data (has successor)
    //! @return array{id:int,name:string,image:string,type1:string,type2:string,successor:array{name:string,url:string}}
    public static function getBulbasaurWithEvolution(): array
    {
        return [
            'id' => 1,
            'name' => 'Bulbasaur',
            'image' => 'https://img.example/bulbasaur.png',
            'type1' => 'grass',
            'type2' => 'poison',
            'successor' => [
                'name' => 'Ivysaur',
                'url' => '/dex/2'
            ]
        ];
    }

    //! @brief Get test data for Venusaur with evolution data (has precursor)
    //! @return array{id:int,name:string,image:string,type1:string,type2:string,precursor:array{name:string,url:string}}
    public static function getVenusaurWithEvolution(): array
    {
        return [
            'id' => 3,
            'name' => 'Venusaur',
            'image' => 'https://img.example/venusaur.png',
            'type1' => 'grass',
            'type2' => 'poison',
            'precursor' => [
                'name' => 'Ivysaur',
                'url' => '/dex/2'
            ]
        ];
    }

    //! @brief Get test data for Eevee (ID: 133) - multiple evolutions
    //! @return array{id:int,name:string,image:string,type1:string}
    public static function getEevee(): array
    {
        return [
            'id' => 133,
            'name' => 'Eevee',
            'image' => 'https://img.example/eevee.png',
            'type1' => 'normal',
        ];
    }

    //! @brief Get test data for Eevee with multiple evolution data
    //! @return array{id:int,name:string,image:string,type1:string,successors:array<array{name:string,url:string}>}
    public static function getEeveeWithMultipleEvolutions(): array
    {
        return [
            'id' => 133,
            'name' => 'Eevee',
            'image' => 'https://img.example/eevee.png',
            'type1' => 'normal',
            'successors' => [
                ['name' => 'Vaporeon', 'url' => '/dex/vaporeon'],
                ['name' => 'Jolteon', 'url' => '/dex/jolteon'],
                ['name' => 'Flareon', 'url' => '/dex/flareon'],
                ['name' => 'Espeon', 'url' => '/dex/espeon'],
                ['name' => 'Umbreon', 'url' => '/dex/umbreon'],
                ['name' => 'Leafeon', 'url' => '/dex/leafeon'],
                ['name' => 'Glaceon', 'url' => '/dex/glaceon'],
                ['name' => 'Sylveon', 'url' => '/dex/sylveon']
            ]
        ];
    }
}
