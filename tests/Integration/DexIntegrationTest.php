<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Service\PokeApiService;
use App\Presenter\DexPresenter;
use App\Type\Result;
use App\Type\MonsterIdentifier;
use Tests\TestData\PokemonTestData;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

//! @brief Integration test for Dex flow from service to template rendering
//!
//! Tests that PokeApiService -> DexPresenter -> Twig template produces correct output
//! Uses local test data to avoid API calls
class DexIntegrationTest extends TestCase
{
    private Environment $twig; //!< Twig environment for template rendering
    private string $testCacheDir; //!< Temporary cache directory

    //! @brief Set up test environment before each test
    protected function setUp(): void
    {
        $this->testCacheDir = sys_get_temp_dir() . '/dex_integration_test_' . uniqid();
        @mkdir($this->testCacheDir, 0777, true);

        //! Set up Twig environment
        $loader = new FilesystemLoader(__DIR__ . '/../../templates');
        $this->twig = new Environment($loader, [
            'cache' => false,
            'debug' => false,
        ]);
    }

    //! @brief Clean up test environment after each test
    protected function tearDown(): void
    {
        if (is_dir($this->testCacheDir)) {
            array_map('unlink', glob($this->testCacheDir . '/*') ?: []);
            @rmdir($this->testCacheDir);
        }
    }

    public function testSingleTypePokemonIntegration(): void
    {
        //! @section Arrange
        $cacheDir = $this->testCacheDir . '_single_type';
        @mkdir($cacheDir, 0777, true);

        $pokemonJson = PokemonTestData::getCharmanderJson();
        $service = new PokeApiService(function (string $url) use ($pokemonJson): string {
            return $pokemonJson;
        });
        $presenter = new DexPresenter($service, 300);

        try {
            //! @section Act
            $monsterData = $presenter->fetchMonsterData(MonsterIdentifier::fromString('charmander_single'));
            $viewData = $presenter->present($monsterData);
            $rendered = $this->twig->render($viewData['template']->value . '.twig', $viewData);

            //! @section Assert
            $this->assertStringContainsString('Charmander', $rendered);
            $this->assertStringContainsString('#4', $rendered);
            $this->assertStringContainsString('fire', $rendered);
            $this->assertStringContainsString('data-type1="fire"', $rendered);
            $this->assertStringContainsString('data-type2=""', $rendered);

            //! Ensure only one type is rendered
            $this->assertEquals(1, substr_count($rendered, 'class="type type-fire"'));
            $this->assertStringNotContainsString('type-grass', $rendered);
            $this->assertStringNotContainsString('type-poison', $rendered);
        } finally {
            if (is_dir($cacheDir)) {
                array_map('unlink', glob($cacheDir . '/*') ?: []);
                @rmdir($cacheDir);
            }
        }
    }

    public function testDualTypePokemonIntegration(): void
    {
        //! @section Arrange
        $cacheDir = $this->testCacheDir . '_dual_type';
        @mkdir($cacheDir, 0777, true);

        $pokemonJson = PokemonTestData::getBulbasaurJson();
        $service = new PokeApiService(function (string $url) use ($pokemonJson): string {
            return $pokemonJson;
        });
        $presenter = new DexPresenter($service, 300);

        try {
            //! @section Act
            $monsterData = $presenter->fetchMonsterData(MonsterIdentifier::fromString('bulbasaur_dual'));
            $viewData = $presenter->present($monsterData);
            $rendered = $this->twig->render($viewData['template']->value . '.twig', $viewData);

            //! @section Assert
            $this->assertStringContainsString('Bulbasaur', $rendered);
            $this->assertStringContainsString('#1', $rendered);
            $this->assertStringContainsString('grass', $rendered);
            $this->assertStringContainsString('poison', $rendered);
            $this->assertStringContainsString('data-type1="grass"', $rendered);
            $this->assertStringContainsString('data-type2="poison"', $rendered);

            //! Ensure both types are rendered
            $this->assertEquals(1, substr_count($rendered, 'class="type type-grass"'));
            $this->assertEquals(1, substr_count($rendered, 'class="type type-poison"'));
        } finally {
            if (is_dir($cacheDir)) {
                array_map('unlink', glob($cacheDir . '/*') ?: []);
                @rmdir($cacheDir);
            }
        }
    }

    public function testPokemonWithNoTypesIntegration(): void
    {
        //! @section Arrange
        $cacheDir = $this->testCacheDir . '_no_types';
        @mkdir($cacheDir, 0777, true);

        $pokemonJson = PokemonTestData::getTypelessPokemonJson();
        $service = new PokeApiService(function (string $url) use ($pokemonJson): string {
            return $pokemonJson;
        });
        $presenter = new DexPresenter($service, 300);

        //! @section Arrange
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No primary type found for Pokemon');

        //! @section Act
        $presenter->fetchMonsterData(MonsterIdentifier::fromString('unknown_no_types'));

        if (is_dir($cacheDir)) {
            array_map('unlink', glob($cacheDir . '/*') ?: []);
            @rmdir($cacheDir);
        }
    }

    public function testPokemonTypesAreSortedCorrectlyIntegration(): void
    {
        //! @section Arrange
        $cacheDir = $this->testCacheDir . '_sorted_types';
        @mkdir($cacheDir, 0777, true);

        //! Create test data with types in wrong order to test sorting
        $pokemonJson = json_encode([
            'id' => 25,
            'name' => 'pikachu',
            'types' => [
                ['slot' => 2, 'type' => ['name' => 'flying']],  // Wrong order
                ['slot' => 1, 'type' => ['name' => 'electric']] // Should be first
            ],
            'sprites' => [
                'other' => [
                    'official-artwork' => [
                        'front_default' => 'https://img.example/pikachu.png'
                    ]
                ]
            ]
        ]);

        $service = new PokeApiService(function (string $url) use ($pokemonJson): string {
            return $pokemonJson;
        });
        $presenter = new DexPresenter($service, 300);

        try {
            //! @section Act
            $monsterData = $presenter->fetchMonsterData(MonsterIdentifier::fromString('pikachu_sorted'));
            $viewData = $presenter->present($monsterData);
            $rendered = $this->twig->render($viewData['template']->value . '.twig', $viewData);

            //! @section Assert
            $this->assertStringContainsString('data-type1="electric"', $rendered);
            $this->assertStringContainsString('data-type2="flying"', $rendered);

            //! Types should appear in correct order in HTML
            $type1Pos = strpos($rendered, 'type-electric');
            $type2Pos = strpos($rendered, 'type-flying');
            $this->assertLessThan($type2Pos, $type1Pos);
        } finally {
            if (is_dir($cacheDir)) {
                array_map('unlink', glob($cacheDir . '/*') ?: []);
                @rmdir($cacheDir);
            }
        }
    }
}
