<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Service\PokeApiService;
use App\Type\Result;
use App\Type\MonsterData;
use App\Type\MonsterIdentifier;
use App\Type\MonsterType;
use App\Type\FilePath;

final class PokeApiServiceTest extends TestCase
{
    private const CACHE_TTL_SECONDS = 300;
    private const STALE_CACHE_TTL = 0;

    //! @brief Create a PokeAPI service with mock HTTP client
    //! @param responses Array of JSON responses to return in sequence
    //! @param expectedUrls Array of expected URL patterns (optional)
    //! @return PokeApiService configured with mock client
    private function createServiceWithMockHttp(
        array $responses,
        ?array $expectedUrls = null
    ): PokeApiService {
        $callCount = 0;
        return new PokeApiService(function (string $url) use ($responses, $expectedUrls, &$callCount): string {
            if ($expectedUrls !== null && isset($expectedUrls[$callCount])) {
                $this->assertStringContainsString($expectedUrls[$callCount], $url);
            }
            $response = $responses[$callCount] ?? throw new \RuntimeException('Unexpected HTTP call');
            $callCount++;
            return $response;
        });
    }

    //! @brief Create standard Pokemon JSON response data
    //! @param id Pokemon ID
    //! @param name Pokemon name
    //! @param types Array of type data
    //! @param imageUrl Pokemon image URL
    //! @return string JSON encoded Pokemon data
    private function createPokemonJson(int $id, string $name, array $types, string $imageUrl): string
    {
        return json_encode([
            'id' => $id,
            'name' => $name,
            'types' => $types,
            'sprites' => [
                'other' => [
                    'official-artwork' => [
                        'front_default' => $imageUrl
                    ]
                ]
            ]
        ]);
    }

    public function testFetchPokemonByNameMapsCoreFields(): void
    {
        //! @section Arrange
        $cacheDir = $this->createTestCacheDir();
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

        $service = new PokeApiService(function (string $url) use ($bulbasaurJson): string {
            $this->assertStringContainsString('/pokemon/bulbasaur', $url);
            return $bulbasaurJson;
        });

        try {
            //! @section Act
            $result = $service->fetchMonster(MonsterIdentifier::fromString('bulbasaur'), FilePath::fromString($cacheDir));

            //! @section Assert
            $this->assertTrue($result->isSuccess());
            $monster = $result->getValue();
            $this->assertInstanceOf(MonsterData::class, $monster);
            $this->assertSame(1, $monster->id);
            $this->assertSame('Bulbasaur', $monster->name);
            $this->assertSame('https://img.example/bulbasaur.png', $monster->image);
        $this->assertSame(MonsterType::GRASS, $monster->type1);
        $this->assertSame(MonsterType::POISON, $monster->type2);
        } finally {
            $this->cleanupTestCacheDir(FilePath::fromString($cacheDir));
        }
    }

    public function testFetchPokemonByIdHandlesSingleType(): void
    {
        //! @section Arrange
        $dittoJson = $this->createPokemonJson(132, 'ditto', [
            ['slot' => 1, 'type' => ['name' => 'normal']],
        ], 'https://img.example/ditto.png');

        $service = $this->createServiceWithMockHttp(
            [$dittoJson],
            ['/pokemon/132']
        );

        //! @section Act
        $result = $service->fetchMonster(MonsterIdentifier::fromString('132'));

        //! @section Assert
        $this->assertTrue($result->isSuccess());
        $monster = $result->getValue();
        $this->assertSame(132, $monster->id);
        $this->assertSame('Ditto', $monster->name);
        $this->assertSame('https://img.example/ditto.png', $monster->image);
        $this->assertSame(MonsterType::NORMAL, $monster->type1);
        $this->assertNull($monster->type2);
    }

    public function testFetchPokemonSortsTypesBySlot(): void
    {
        //! @section Arrange - types in wrong order
        $pokemonJson = $this->createPokemonJson(25, 'pikachu', [
            ['slot' => 2, 'type' => ['name' => 'flying']],
            ['slot' => 1, 'type' => ['name' => 'electric']],
        ], 'https://img.example/pikachu.png');

        $service = $this->createServiceWithMockHttp([$pokemonJson]);

        //! @section Act
        $result = $service->fetchMonster(MonsterIdentifier::fromString('pikachu'));

        //! @section Assert - should be sorted by slot
        $this->assertTrue($result->isSuccess());
        $monster = $result->getValue();
        $this->assertSame(MonsterType::ELECTRIC, $monster->type1);
        $this->assertSame(MonsterType::FLYING, $monster->type2);
    }

    public function testFetchPokemonHandlesMissingOfficialArtwork(): void
    {
        //! @section Arrange - missing official-artwork, should fall back to front_default
        $cacheDir = $this->createTestCacheDir();
        $pokemonJson = json_encode([
            'id' => 25,
            'name' => 'pikachu',
            'types' => [['slot' => 1, 'type' => ['name' => 'electric']]],
            'sprites' => [
                'front_default' => 'https://img.example/pikachu-fallback.png'
            ]
        ]);

        $service = new PokeApiService(function (string $url) use ($pokemonJson): string {
            return $pokemonJson;
        });

        try {
            //! @section Act
            $result = $service->fetchMonster(MonsterIdentifier::fromString('pikachu'), FilePath::fromString($cacheDir));

            //! @section Assert
            $this->assertTrue($result->isSuccess());
            $monster = $result->getValue();
            $this->assertSame('https://img.example/pikachu-fallback.png', $monster->image);
        } finally {
            $this->cleanupTestCacheDir(FilePath::fromString($cacheDir));
        }
    }

    public function testFetchPokemonHandlesMissingImageCompletely(): void
    {
        //! @section Arrange - no image data at all
        $cacheDir = $this->createTestCacheDir();
        $pokemonJson = json_encode([
            'id' => 25,
            'name' => 'pikachu',
            'types' => [['slot' => 1, 'type' => ['name' => 'electric']]]
        ]);

        $service = new PokeApiService(function (string $url) use ($pokemonJson): string {
            return $pokemonJson;
        });

        try {
            //! @section Act
            $result = $service->fetchMonster(MonsterIdentifier::fromString('pikachu'), FilePath::fromString($cacheDir));

            //! @section Assert
            $this->assertTrue($result->isSuccess());
            $monster = $result->getValue();
            $this->assertSame('', $monster->image);
        } finally {
            $this->cleanupTestCacheDir(FilePath::fromString($cacheDir));
        }
    }

    public function testFetchPokemonTrimsInput(): void
    {
        //! @section Arrange
        $pokemonJson = $this->createPokemonJson(1, 'bulbasaur', [
            ['slot' => 1, 'type' => ['name' => 'grass']]
        ], 'https://img.example/bulbasaur.png');

        $service = $this->createServiceWithMockHttp(
            [$pokemonJson],
            ['/pokemon/bulbasaur'] // Should not contain spaces
        );

        //! @section Act
        $result = $service->fetchMonster(MonsterIdentifier::fromString('  bulbasaur  '));

        //! @section Assert
        $this->assertTrue($result->isSuccess());
        $monster = $result->getValue();
        $this->assertSame('Bulbasaur', $monster->name);
    }

    public function testFetchPokemonReturnsFailureOnMalformedJson(): void
    {
        //! @section Arrange
        $cacheDir = $this->createTestCacheDir();
        $service = new PokeApiService(function (string $url): string {
            return 'invalid json';
        });

        try {
            //! @section Act
            $result = $service->fetchMonster(MonsterIdentifier::fromString('pikachu'), FilePath::fromString($cacheDir));

            //! @section Assert
            $this->assertTrue($result->isFailure());
            $this->assertStringContainsString('Invalid JSON response', $result->getError());
        } finally {
            $this->cleanupTestCacheDir(FilePath::fromString($cacheDir));
        }
    }

    public function testFetchPokemonReturnsFailureOnNetworkFailureWithoutCache(): void
    {
        //! @section Arrange
        $cacheDir = $this->createTestCacheDir();
        $service = new PokeApiService(function (string $url): string {
            throw new \RuntimeException('Network failure');
        });

        try {
            //! @section Act
            $result = $service->fetchMonster(MonsterIdentifier::fromString('pikachu'), FilePath::fromString($cacheDir));

            //! @section Assert
            $this->assertTrue($result->isFailure());
            $this->assertStringContainsString('Failed to fetch Pokemon data: Network failure', $result->getError());
        } finally {
            $this->cleanupTestCacheDir(FilePath::fromString($cacheDir));
        }
    }

    public function testTitleCaseConversion(): void
    {
        //! @section Arrange
        $pokemonJson = json_encode([
            'id' => 1,
            'name' => 'BULBASAUR',
            'types' => [['slot' => 1, 'type' => ['name' => 'grass']]],
            'sprites' => ['front_default' => 'https://img.example/bulbasaur.png']
        ]);

        $service = $this->createServiceWithMockHttp([$pokemonJson]);

        //! @section Act
        $result = $service->fetchMonster(MonsterIdentifier::fromString('bulbasaur'));

        //! @section Assert
        $this->assertTrue($result->isSuccess());
        $monster = $result->getValue();
        $this->assertSame('Bulbasaur', $monster->name);
    }

    public function testTitleCaseHandlesEmptyName(): void
    {
        //! @section Arrange
        $cacheDir = $this->createTestCacheDir();
        $pokemonJson = json_encode([
            'id' => 1,
            'name' => '',
            'types' => [['slot' => 1, 'type' => ['name' => 'grass']]],
            'sprites' => ['front_default' => 'https://img.example/bulbasaur.png']
        ]);

        $service = new PokeApiService(function (string $url) use ($pokemonJson): string {
            return $pokemonJson;
        });

        try {
            //! @section Act
            $result = $service->fetchMonster(MonsterIdentifier::fromString('bulbasaur'), FilePath::fromString($cacheDir));

            //! @section Assert
            $this->assertTrue($result->isSuccess());
            $monster = $result->getValue();
            $this->assertSame('', $monster->name);
        } finally {
            $this->cleanupTestCacheDir(FilePath::fromString($cacheDir));
        }
    }

    public function testType2IsOptionalForSingleTypePokemon(): void
    {
        //! @section Arrange
        $cacheDir = $this->createTestCacheDir();
        $pokemonJson = $this->createPokemonJson(25, 'pikachu', [
            ['slot' => 1, 'type' => ['name' => 'electric']]
        ], 'https://img.example/pikachu.png');

        $service = new PokeApiService(function (string $url) use ($pokemonJson): string {
            return $pokemonJson;
        });

        try {
            //! @section Act
            $result = $service->fetchMonster(MonsterIdentifier::fromString('pikachu'), FilePath::fromString($cacheDir));

            //! @section Assert
            $this->assertTrue($result->isSuccess());
            $monster = $result->getValue();
            $this->assertSame(MonsterType::ELECTRIC, $monster->type1);
            $this->assertNull($monster->type2);
        } finally {
            $this->cleanupTestCacheDir(FilePath::fromString($cacheDir));
        }
    }

    public function testType2IsPresentForDualTypePokemon(): void
    {
        //! @section Arrange
        $cacheDir = $this->createTestCacheDir();
        $pokemonJson = $this->createPokemonJson(1, 'bulbasaur', [
            ['slot' => 1, 'type' => ['name' => 'grass']],
            ['slot' => 2, 'type' => ['name' => 'poison']]
        ], 'https://img.example/bulbasaur.png');

        $service = new PokeApiService(function (string $url) use ($pokemonJson): string {
            return $pokemonJson;
        });

        try {
            //! @section Act
            $result = $service->fetchMonster(MonsterIdentifier::fromString('bulbasaur'), FilePath::fromString($cacheDir));

            //! @section Assert
            $this->assertTrue($result->isSuccess());
            $monster = $result->getValue();
        $this->assertSame(MonsterType::GRASS, $monster->type1);
        $this->assertSame(MonsterType::POISON, $monster->type2);
        } finally {
            $this->cleanupTestCacheDir(FilePath::fromString($cacheDir));
        }
    }

    public function testType1AlwaysExistsEvenWithMissingTypes(): void
    {
        //! @section Arrange
        $cacheDir = $this->createTestCacheDir();
        $pokemonJson = json_encode([
            'id' => 25,
            'name' => 'pikachu',
            'sprites' => ['front_default' => 'https://img.example/pikachu.png']
            // Missing types array
        ]);

        $service = new PokeApiService(function (string $url) use ($pokemonJson): string {
            return $pokemonJson;
        });

        //! @section Act
        $result = $service->fetchMonster(MonsterIdentifier::fromString('pikachu'), FilePath::fromString($cacheDir));

        //! @section Assert
        $this->assertTrue($result->isFailure());
        $this->assertStringContainsString('No primary type found for Pokemon', $result->getError());

        $this->cleanupTestCacheDir(FilePath::fromString($cacheDir));
    }

    //! @brief Create isolated cache directory for testing
    //! @return string Path to temporary cache directory
    private function createTestCacheDir(): string
    {
        $tmp = sys_get_temp_dir() . '/pokeapi_test_cache_' . uniqid();
        @mkdir($tmp, 0777, true);
        return $tmp;
    }

    //! @brief Clean up test cache directory
    //! @param cacheDir Directory path to clean (FilePath or string)
    private function cleanupTestCacheDir(FilePath|string $cacheDir): void
    {
        $cacheDirPath = $cacheDir instanceof FilePath ? $cacheDir->getValue() : $cacheDir;
        if (is_dir($cacheDirPath)) {
            array_map('unlink', glob($cacheDirPath . '/*') ?: []);
            @rmdir($cacheDirPath);
        }
    }

    public function testCachesSuccessfulResponseAndServesFromCache(): void
    {
        //! @section Arrange
        $cacheDir = $this->createTestCacheDir();
        $callCount = 0;

        $pikachuJson = $this->createPokemonJson(25, 'pikachu', [
            ['slot' => 1, 'type' => ['name' => 'electric']]
        ], 'https://img/pika.png');

        $service = new PokeApiService(function (string $url) use (&$callCount, $pikachuJson): string {
            $callCount++;
            return $pikachuJson;
        });

        try {
            //! @section Act: first call populates cache
            $monster1 = $service->fetchMonster(MonsterIdentifier::fromString('pikachu'), FilePath::fromString($cacheDir), self::CACHE_TTL_SECONDS);
            //! @section Act: second call should use cache (no http call)
            $monster2 = $service->fetchMonster(MonsterIdentifier::fromString('pikachu'), FilePath::fromString($cacheDir), self::CACHE_TTL_SECONDS);

            //! @section Assert
            $this->assertSame(1, $callCount);
            $this->assertTrue($monster1->isSuccess());
            $this->assertTrue($monster2->isSuccess());
            $this->assertEquals($monster1->getValue(), $monster2->getValue());
        } finally {
            $this->cleanupTestCacheDir(FilePath::fromString($cacheDir));
        }
    }

    public function testUsesStaleCacheOnNetworkFailure(): void
    {
        //! @section Arrange
        $cacheDir = $this->createTestCacheDir();
        $callCount = 0;

        $charmanderJson = $this->createPokemonJson(4, 'charmander', [
            ['slot' => 1, 'type' => ['name' => 'fire']]
        ], 'https://img/char.png');

        $responses = [$charmanderJson, false];
        $service = new PokeApiService(function (string $url) use (&$callCount, &$responses): string {
            $callCount++;
            $resp = array_shift($responses);
            if ($resp === false) {
                throw new \RuntimeException('network failure');
            }
            return $resp;
        });

        try {
            //! @section Act: first call populates cache
            $monster1 = $service->fetchMonster(MonsterIdentifier::fromString('4'), FilePath::fromString($cacheDir), self::STALE_CACHE_TTL);
            //! @section Act: second call fails network but should return cached data
            $monster2 = $service->fetchMonster(MonsterIdentifier::fromString('4'), FilePath::fromString($cacheDir), self::STALE_CACHE_TTL);

            //! @section Assert
            $this->assertSame(2, $callCount);
            $this->assertTrue($monster1->isSuccess());
            $this->assertTrue($monster2->isSuccess());
            $this->assertEquals($monster1->getValue(), $monster2->getValue());
        } finally {
            $this->cleanupTestCacheDir(FilePath::fromString($cacheDir));
        }
    }

    public function testCreatesCacheDirectoryIfNotExists(): void
    {
        //! @section Arrange
        $cacheDir = sys_get_temp_dir() . '/pokeapi_test_new_dir_' . uniqid();
        $this->assertDirectoryDoesNotExist($cacheDir);

        $pikachuJson = $this->createPokemonJson(25, 'pikachu', [
            ['slot' => 1, 'type' => ['name' => 'electric']]
        ], 'https://img/pika.png');

        $service = new PokeApiService(function (string $url) use ($pikachuJson): string {
            return $pikachuJson;
        });

        try {
            //! @section Act
            $service->fetchMonster(MonsterIdentifier::fromString('pikachu'), FilePath::fromString($cacheDir));

            //! @section Assert
            $this->assertDirectoryExists($cacheDir);
            $matches = glob($cacheDir . '/v*_pokemon_' . md5('pikachu') . '.json');
            if (!$matches) {
                // Backward compatibility if versioning not applied
                $matches = glob($cacheDir . '/pokemon_' . md5('pikachu') . '.json');
            }
            $this->assertNotFalse($matches);
            $this->assertNotEmpty($matches);
        } finally {
            $this->cleanupTestCacheDir(FilePath::fromString($cacheDir));
        }
    }

    public function test_cache_version_bump_triggers_refetch_and_new_file(): void
    {
        //! @section Arrange
        $cacheDir = $this->createTestCacheDir();
        $ttl = 3600;
        $callCount = 0;

        $jsonV1 = $this->createPokemonJson(25, 'pikachu', [
            ['slot' => 1, 'type' => ['name' => 'electric']]
        ], 'https://img.example/pikachu.png');
        $jsonV2 = $this->createPokemonJson(25, 'pikachu', [
            ['slot' => 1, 'type' => ['name' => 'electric']]
        ], 'https://img.example/pikachu-v2.png');

        $responses = [$jsonV1, $jsonV2];
        $service = new PokeApiService(function (string $url) use (&$callCount, &$responses): string {
            $callCount++;
            return array_shift($responses) ?? throw new \RuntimeException('No more responses');
        });

        try {
            //! @section Act: first call writes v1 cache
            $first = $service->fetchMonster(MonsterIdentifier::fromString('pikachu'), FilePath::fromString($cacheDir), $ttl, 'v1');
            //! @section Act: second call with new version should refetch and write v2 cache
            $second = $service->fetchMonster(MonsterIdentifier::fromString('pikachu'), FilePath::fromString($cacheDir), $ttl, 'v2');

            //! @section Assert
            $this->assertTrue($first->isSuccess());
            $this->assertTrue($second->isSuccess());
            $this->assertSame(2, $callCount, 'Version bump should bypass prior cache and refetch');

            $v1Files = glob($cacheDir . '/v1_pokemon_' . md5('pikachu') . '.json');
            $v2Files = glob($cacheDir . '/v2_pokemon_' . md5('pikachu') . '.json');
            $this->assertNotEmpty($v1Files, 'v1 cache file should exist');
            $this->assertNotEmpty($v2Files, 'v2 cache file should exist');
        } finally {
            $this->cleanupTestCacheDir(FilePath::fromString($cacheDir));
        }
    }

    public function test_version_change_fetches_even_with_fresh_cache(): void
    {
        //! @section Arrange
        $cacheDir = $this->createTestCacheDir();
        $ttl = 3600;
        $callCount = 0;

        $json = $this->createPokemonJson(1, 'bulbasaur', [
            ['slot' => 1, 'type' => ['name' => 'grass']]
        ], 'https://img.example/bulbasaur.png');

        $service = new PokeApiService(function (string $url) use (&$callCount, $json): string {
            $callCount++;
            return $json;
        });

        try {
            //! @section Act: fetch v1 (populates cache)
            $service->fetchMonster(MonsterIdentifier::fromString('bulbasaur'), FilePath::fromString($cacheDir), $ttl, 'v1');
            //! @section Act: fetch v2 within TTL should still call HTTP once more
            $service->fetchMonster(MonsterIdentifier::fromString('bulbasaur'), FilePath::fromString($cacheDir), $ttl, 'v2');

            //! @section Assert
            $this->assertSame(2, $callCount);
        } finally {
            $this->cleanupTestCacheDir(FilePath::fromString($cacheDir));
        }
    }

    public function testReturnsFailureWhenNetworkFailsAndNoStaleCache(): void
    {
        //! @section Arrange
        $cacheDir = $this->createTestCacheDir();
        $service = new PokeApiService(function (string $url): string {
            throw new \RuntimeException('Network failure');
        });

        try {
            //! @section Act
            $result = $service->fetchMonster(MonsterIdentifier::fromString('pikachu'), FilePath::fromString($cacheDir));

            //! @section Assert
            $this->assertTrue($result->isFailure());
            $this->assertStringContainsString('Failed to fetch Pokemon data: Network failure', $result->getError());
        } finally {
            $this->cleanupTestCacheDir(FilePath::fromString($cacheDir));
        }
    }

    public function testCacheExpiration(): void
    {
        //! @section Arrange
        $cacheDir = $this->createTestCacheDir();
        $callCount = 0;

        $pikachuJson = $this->createPokemonJson(25, 'pikachu', [
            ['slot' => 1, 'type' => ['name' => 'electric']]
        ], 'https://img/pika.png');

        $service = new PokeApiService(function (string $url) use (&$callCount, $pikachuJson): string {
            $callCount++;
            return $pikachuJson;
        });

        try {
            //! @section Act: populate cache
            $service->fetchMonster(MonsterIdentifier::fromString('pikachu'), FilePath::fromString($cacheDir), 0); // TTL = 0 (immediately stale)

            //! @section Act: second call should hit network again due to expired cache
            $service->fetchMonster(MonsterIdentifier::fromString('pikachu'), FilePath::fromString($cacheDir), 0);

            //! @section Assert
            $this->assertSame(2, $callCount);
        } finally {
            $this->cleanupTestCacheDir(FilePath::fromString($cacheDir));
        }
    }

    public function test_fetch_monster_includes_evolution_data_when_available(): void
    {
        //! @section Arrange - Test basic functionality first
        $cacheDir = $this->createTestCacheDir();
        $bulbasaurJson = json_encode([
            'id' => 1,
            'name' => 'bulbasaur',
            'types' => [
                ['slot' => 1, 'type' => ['name' => 'grass']],
                ['slot' => 2, 'type' => ['name' => 'poison']]
            ],
            'sprites' => [
                'other' => [
                    'official-artwork' => [
                        'front_default' => 'https://img.example/bulbasaur.png'
                    ]
                ]
            ]
        ]);

        $service = new PokeApiService(function (string $url) use ($bulbasaurJson): string {
            return $bulbasaurJson;
        });

        try {
            //! @section Act
            $result = $service->fetchMonster(MonsterIdentifier::fromString('bulbasaur'), FilePath::fromString($cacheDir));

            //! @section Assert
            $this->assertTrue($result->isSuccess());
            $monster = $result->getValue();
            $this->assertSame(1, $monster->id);
            $this->assertSame('Bulbasaur', $monster->name);
        $this->assertSame(MonsterType::GRASS, $monster->type1);
        $this->assertSame(MonsterType::POISON, $monster->type2);
            $this->assertEmpty($monster->successors); // No evolution data expected
        } finally {
            $this->cleanupTestCacheDir(FilePath::fromString($cacheDir));
        }
    }

    public function test_fetch_monster_handles_multiple_evolutions(): void
    {
        //! @section Arrange - Test Eevee with multiple evolutions
        $cacheDir = $this->createTestCacheDir();
        $eeveeJson = json_encode([
            'id' => 133,
            'name' => 'eevee',
            'types' => [
                ['slot' => 1, 'type' => ['name' => 'normal']]
            ],
            'sprites' => [
                'other' => [
                    'official-artwork' => [
                        'front_default' => 'https://img.example/eevee.png'
                    ]
                ]
            ],
            'species' => [
                'url' => 'https://pokeapi.co/api/v2/pokemon-species/133/'
            ]
        ]);

        $speciesJson = json_encode([
            'evolution_chain' => [
                'url' => 'https://pokeapi.co/api/v2/evolution-chain/67/'
            ]
        ]);

        $evolutionChainJson = json_encode([
            'chain' => [
                'species' => ['name' => 'eevee'],
                'evolves_to' => [
                    ['species' => ['name' => 'vaporeon'], 'evolves_to' => []],
                    ['species' => ['name' => 'jolteon'], 'evolves_to' => []],
                    ['species' => ['name' => 'flareon'], 'evolves_to' => []],
                    ['species' => ['name' => 'espeon'], 'evolves_to' => []],
                    ['species' => ['name' => 'umbreon'], 'evolves_to' => []],
                    ['species' => ['name' => 'leafeon'], 'evolves_to' => []],
                    ['species' => ['name' => 'glaceon'], 'evolves_to' => []],
                    ['species' => ['name' => 'sylveon'], 'evolves_to' => []]
                ]
            ]
        ]);

        $service = new PokeApiService(function (string $url) use ($eeveeJson, $speciesJson, $evolutionChainJson): string {
            if (str_contains($url, 'pokemon-species')) {
                return $speciesJson;
            } elseif (str_contains($url, 'evolution-chain')) {
                return $evolutionChainJson;
            } elseif (str_contains($url, 'pokemon/eevee')) {
                return $eeveeJson;
            } else {
                throw new \RuntimeException('Unexpected URL: ' . $url);
            }
        });

        try {
            //! @section Act
            $result = $service->fetchMonster(MonsterIdentifier::fromString('eevee'), FilePath::fromString($cacheDir));

            //! @section Assert
            $this->assertTrue($result->isSuccess());
            $monster = $result->getValue();
            $this->assertSame(133, $monster->id);
            $this->assertSame('Eevee', $monster->name);
            $this->assertCount(8, $monster->successors);

            // Check that all evolutions are present
            $evolutionNames = array_map(fn($evo) => $evo->name, $monster->successors);
            $this->assertContains('Vaporeon', $evolutionNames);
            $this->assertContains('Jolteon', $evolutionNames);
            $this->assertContains('Flareon', $evolutionNames);
            $this->assertContains('Espeon', $evolutionNames);
            $this->assertContains('Umbreon', $evolutionNames);
            $this->assertContains('Leafeon', $evolutionNames);
            $this->assertContains('Glaceon', $evolutionNames);
            $this->assertContains('Sylveon', $evolutionNames);

            // Check URLs
            $this->assertStringContainsString('/dex/vaporeon', $monster->successors[0]->url);
        } finally {
            $this->cleanupTestCacheDir(FilePath::fromString($cacheDir));
        }
    }

    public function test_caches_species_and_evolution_requests_per_ttl(): void
    {
        //! @section Arrange
        $cacheDir = $this->createTestCacheDir();
        $ttl = 600;

        $counts = [
            'pokemon' => 0,
            'species' => 0,
            'evolution' => 0,
        ];

        $eeveeJson = json_encode([
            'id' => 133,
            'name' => 'eevee',
            'types' => [
                ['slot' => 1, 'type' => ['name' => 'normal']]
            ],
            'sprites' => [
                'other' => [
                    'official-artwork' => [
                        'front_default' => 'https://img.example/eevee.png'
                    ]
                ]
            ],
            'species' => [
                'url' => 'https://pokeapi.co/api/v2/pokemon-species/133/'
            ]
        ]);

        $speciesJson = json_encode([
            'evolution_chain' => [
                'url' => 'https://pokeapi.co/api/v2/evolution-chain/67/'
            ]
        ]);

        $evolutionChainJson = json_encode([
            'chain' => [
                'species' => ['name' => 'eevee'],
                'evolves_to' => [
                    ['species' => ['name' => 'vaporeon'], 'evolves_to' => []]
                ]
            ]
        ]);

        $service = new PokeApiService(function (string $url) use (&$counts, $eeveeJson, $speciesJson, $evolutionChainJson): string {
            if (str_contains($url, '/pokemon/eevee')) {
                $counts['pokemon']++;
                return $eeveeJson;
            }
            if (str_contains($url, 'pokemon-species')) {
                $counts['species']++;
                return $speciesJson;
            }
            if (str_contains($url, 'evolution-chain')) {
                $counts['evolution']++;
                return $evolutionChainJson;
            }
            throw new \RuntimeException('Unexpected URL: ' . $url);
        });

        try {
            //! @section Act
            $result1 = $service->fetchMonster(MonsterIdentifier::fromString('eevee'), FilePath::fromString($cacheDir), $ttl);
            $result2 = $service->fetchMonster(MonsterIdentifier::fromString('eevee'), FilePath::fromString($cacheDir), $ttl);

            //! @section Assert
            $this->assertTrue($result1->isSuccess());
            $this->assertTrue($result2->isSuccess());
            $this->assertSame(1, $counts['pokemon'], 'Pokemon endpoint should be called once within TTL');
            $this->assertSame(1, $counts['species'], 'Species endpoint should be called once within TTL');
            $this->assertSame(1, $counts['evolution'], 'Evolution chain endpoint should be called once within TTL');
        } finally {
            $this->cleanupTestCacheDir(FilePath::fromString($cacheDir));
        }
    }

    public function test_uses_canonical_cache_between_name_and_id(): void
    {
        //! @section Arrange
        $cacheDir = $this->createTestCacheDir();
        $ttl = 3600;
        $callCount = 0;

        $pikachuJson = $this->createPokemonJson(25, 'pikachu', [
            ['slot' => 1, 'type' => ['name' => 'electric']]
        ], 'https://img.example/pikachu.png');

        $service = new PokeApiService(function (string $url) use (&$callCount, $pikachuJson): string {
            $callCount++;
            return $pikachuJson;
        });

        try {
            //! @section Act: populate cache by name
            $first = $service->fetchMonster(MonsterIdentifier::fromString('pikachu'), FilePath::fromString($cacheDir), $ttl);
            //! @section Act: fetch by numeric ID should reuse same cache without HTTP
            $second = $service->fetchMonster(MonsterIdentifier::fromString('25'), FilePath::fromString($cacheDir), $ttl);

            //! @section Assert
            $this->assertTrue($first->isSuccess());
            $this->assertTrue($second->isSuccess());
            $this->assertSame(1, $callCount, 'HTTP should be called only once when switching name->id within TTL');
        } finally {
            $this->cleanupTestCacheDir(FilePath::fromString($cacheDir));
        }
    }
}


