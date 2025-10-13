<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Service\PokeApiService;

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
            $monster = $service->fetchMonster('bulbasaur', $cacheDir);

            //! @section Assert
            $this->assertSame(1, $monster['id']);
            $this->assertSame('Bulbasaur', $monster['name']);
            $this->assertSame('https://img.example/bulbasaur.png', $monster['image']);
            $this->assertSame('grass', $monster['type1']);
            $this->assertSame('poison', $monster['type2']);
        } finally {
            $this->cleanupTestCacheDir($cacheDir);
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
        $monster = $service->fetchMonster('132');

        //! @section Assert
        $this->assertSame(132, $monster['id']);
        $this->assertSame('Ditto', $monster['name']);
        $this->assertSame('https://img.example/ditto.png', $monster['image']);
        $this->assertSame('normal', $monster['type1']);
        $this->assertArrayNotHasKey('type2', $monster);
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
        $monster = $service->fetchMonster('pikachu');

        //! @section Assert - should be sorted by slot
        $this->assertSame('electric', $monster['type1']);
        $this->assertSame('flying', $monster['type2']);
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
            $monster = $service->fetchMonster('pikachu', $cacheDir);

            //! @section Assert
            $this->assertSame('https://img.example/pikachu-fallback.png', $monster['image']);
        } finally {
            $this->cleanupTestCacheDir($cacheDir);
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
            $monster = $service->fetchMonster('pikachu', $cacheDir);

            //! @section Assert
            $this->assertSame('', $monster['image']);
        } finally {
            $this->cleanupTestCacheDir($cacheDir);
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
        $monster = $service->fetchMonster('  bulbasaur  ');

        //! @section Assert
        $this->assertSame('Bulbasaur', $monster['name']);
    }

    public function testFetchPokemonThrowsOnMalformedJson(): void
    {
        //! @section Arrange
        $cacheDir = $this->createTestCacheDir();
        $service = new PokeApiService(function (string $url): string {
            return 'invalid json';
        });

        try {
            //! @section Act & Assert
            $this->expectException(\JsonException::class);
            $service->fetchMonster('pikachu', $cacheDir);
        } finally {
            $this->cleanupTestCacheDir($cacheDir);
        }
    }

    public function testFetchPokemonThrowsOnNetworkFailureWithoutCache(): void
    {
        //! @section Arrange
        $cacheDir = $this->createTestCacheDir();
        $service = new PokeApiService(function (string $url): string {
            throw new \RuntimeException('Network failure');
        });

        try {
            //! @section Act & Assert
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('Network failure');
            $service->fetchMonster('pikachu', $cacheDir);
        } finally {
            $this->cleanupTestCacheDir($cacheDir);
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
        $monster = $service->fetchMonster('bulbasaur');

        //! @section Assert
        $this->assertSame('Bulbasaur', $monster['name']);
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
            $monster = $service->fetchMonster('bulbasaur', $cacheDir);

            //! @section Assert
            $this->assertSame('', $monster['name']);
        } finally {
            $this->cleanupTestCacheDir($cacheDir);
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
            $monster = $service->fetchMonster('pikachu', $cacheDir);

            //! @section Assert
            $this->assertArrayHasKey('type1', $monster);
            $this->assertSame('electric', $monster['type1']);
            $this->assertArrayNotHasKey('type2', $monster);
        } finally {
            $this->cleanupTestCacheDir($cacheDir);
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
            $monster = $service->fetchMonster('bulbasaur', $cacheDir);

            //! @section Assert
            $this->assertArrayHasKey('type1', $monster);
            $this->assertSame('grass', $monster['type1']);
            $this->assertArrayHasKey('type2', $monster);
            $this->assertSame('poison', $monster['type2']);
        } finally {
            $this->cleanupTestCacheDir($cacheDir);
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

        try {
            //! @section Act
            $monster = $service->fetchMonster('pikachu', $cacheDir);

            //! @section Assert
            $this->assertArrayHasKey('type1', $monster);
            $this->assertSame('', $monster['type1']);
            $this->assertArrayNotHasKey('type2', $monster);
        } finally {
            $this->cleanupTestCacheDir($cacheDir);
        }
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
    //! @param cacheDir Directory path to clean
    private function cleanupTestCacheDir(string $cacheDir): void
    {
        if (is_dir($cacheDir)) {
            array_map('unlink', glob($cacheDir . '/*') ?: []);
            @rmdir($cacheDir);
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
            $monster1 = $service->fetchMonster('pikachu', $cacheDir, self::CACHE_TTL_SECONDS);
            //! @section Act: second call should use cache (no http call)
            $monster2 = $service->fetchMonster('pikachu', $cacheDir, self::CACHE_TTL_SECONDS);

            //! @section Assert
            $this->assertSame(1, $callCount);
            $this->assertSame($monster1, $monster2);
        } finally {
            $this->cleanupTestCacheDir($cacheDir);
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
            $monster1 = $service->fetchMonster('4', $cacheDir, self::STALE_CACHE_TTL);
            //! @section Act: second call fails network but should return cached data
            $monster2 = $service->fetchMonster('4', $cacheDir, self::STALE_CACHE_TTL);

            //! @section Assert
            $this->assertSame(2, $callCount);
            $this->assertSame($monster1, $monster2);
        } finally {
            $this->cleanupTestCacheDir($cacheDir);
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
            $service->fetchMonster('pikachu', $cacheDir);

            //! @section Assert
            $this->assertDirectoryExists($cacheDir);
            $this->assertFileExists($cacheDir . '/pokemon_' . md5('pikachu') . '.json');
        } finally {
            $this->cleanupTestCacheDir($cacheDir);
        }
    }

    public function testThrowsWhenNetworkFailsAndNoStaleCache(): void
    {
        //! @section Arrange
        $cacheDir = $this->createTestCacheDir();
        $service = new PokeApiService(function (string $url): string {
            throw new \RuntimeException('Network failure');
        });

        try {
            //! @section Act & Assert
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('Network failure');
            $service->fetchMonster('pikachu', $cacheDir);
        } finally {
            $this->cleanupTestCacheDir($cacheDir);
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
            $service->fetchMonster('pikachu', $cacheDir, 0); // TTL = 0 (immediately stale)
            
            //! @section Act: second call should hit network again due to expired cache
            $service->fetchMonster('pikachu', $cacheDir, 0);

            //! @section Assert
            $this->assertSame(2, $callCount);
        } finally {
            $this->cleanupTestCacheDir($cacheDir);
        }
    }
}


