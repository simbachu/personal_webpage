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
}
