<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Service\PokeApiService;

final class PokeApiServiceTest extends TestCase
{
    public function test_fetch_pokemon_by_name_maps_core_fields(): void
    {
        // Arrange
        $bulbasaurJson = json_encode([
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

        $http = function (string $url): string {
            // will be replaced in closure use
            return '';
        };

        $service = new PokeApiService(function (string $url) use ($bulbasaurJson): string {
            TestCase::assertStringContainsString('/pokemon/bulbasaur', $url);
            return $bulbasaurJson;
        });

        // Act
        $monster = $service->fetchPokemon('bulbasaur');

        // Assert
        $this->assertSame(1, $monster['id']);
        $this->assertSame('Bulbasaur', $monster['name']);
        $this->assertSame('https://img.example/bulbasaur.png', $monster['image']);
        $this->assertSame('grass', $monster['type1']);
        $this->assertSame('poison', $monster['type2']);
    }

    public function test_fetch_pokemon_by_id_handles_single_type(): void
    {
        // Arrange
        $dittoJson = json_encode([
            'id' => 132,
            'name' => 'ditto',
            'types' => [
                ['slot' => 1, 'type' => ['name' => 'normal']],
            ],
            'sprites' => [
                'other' => [
                    'official-artwork' => [
                        'front_default' => 'https://img.example/ditto.png'
                    ]
                ]
            ]
        ]);

        $service = new PokeApiService(function (string $url) use ($dittoJson): string {
            TestCase::assertStringContainsString('/pokemon/132', $url);
            return $dittoJson;
        });

        // Act
        $monster = $service->fetchPokemon('132');

        // Assert
        $this->assertSame(132, $monster['id']);
        $this->assertSame('Ditto', $monster['name']);
        $this->assertSame('https://img.example/ditto.png', $monster['image']);
        $this->assertSame('normal', $monster['type1']);
        $this->assertArrayNotHasKey('type2', $monster);
    }

    public function test_caches_successful_response_and_serves_from_cache(): void
    {
        // Arrange
        $tmp = sys_get_temp_dir() . '/pokeapi_test_cache_' . uniqid();
        @mkdir($tmp);
        $callCount = 0;
        $service = new PokeApiService(function (string $url) use (&$callCount): string {
            $callCount++;
            return json_encode([
                'id' => 25,
                'name' => 'pikachu',
                'types' => [['slot' => 1, 'type' => ['name' => 'electric']]],
                'sprites' => ['other' => ['official-artwork' => ['front_default' => 'https://img/pika.png']]]
            ]);
        });

        // Act: first call populates cache
        $monster1 = $service->fetchPokemon('pikachu', $tmp, 300);
        // Act: second call should use cache (no http call)
        $monster2 = $service->fetchPokemon('pikachu', $tmp, 300);

        // Assert
        $this->assertSame(1, $callCount);
        $this->assertSame($monster1, $monster2);
        // Cleanup
        array_map('unlink', glob($tmp . '/*') ?: []);
        @rmdir($tmp);
    }

    public function test_uses_stale_cache_on_network_failure(): void
    {
        // Arrange
        $tmp = sys_get_temp_dir() . '/pokeapi_test_cache_' . uniqid();
        @mkdir($tmp);
        $callCount = 0;
        $responses = [
            json_encode([
                'id' => 4,
                'name' => 'charmander',
                'types' => [['slot' => 1, 'type' => ['name' => 'fire']]],
                'sprites' => ['other' => ['official-artwork' => ['front_default' => 'https://img/char.png']]]
            ]),
            false,
        ];
        $service = new PokeApiService(function (string $url) use (&$callCount, &$responses): string {
            $callCount++;
            $resp = array_shift($responses);
            if ($resp === false) {
                throw new RuntimeException('network');
            }
            return (string)$resp;
        });

        // Act: first call populates cache
        $monster1 = $service->fetchPokemon('4', $tmp, 300);
        // Act: second call fails network but should return cached data
        $monster2 = $service->fetchPokemon('4', $tmp, 300);

        // Assert
        $this->assertSame(2, $callCount);
        $this->assertSame($monster1, $monster2);
        // Cleanup
        array_map('unlink', glob($tmp . '/*') ?: []);
        @rmdir($tmp);
    }
}


