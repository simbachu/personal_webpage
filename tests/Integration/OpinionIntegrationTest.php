<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Service\PokeApiService;
use App\Service\PokemonOpinionService;
use App\Presenter\DexPresenter;
use App\Type\MonsterIdentifier;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

//! @brief Integration test for opinion display in templates
//!
//! Tests that opinions are properly displayed in the monster card template
final class OpinionIntegrationTest extends TestCase
{
    private Environment $twig;
    private string $testOpinionsFile;

    protected function setUp(): void
    {
        // Create test opinions file
        $this->testOpinionsFile = sys_get_temp_dir() . '/test_opinions_' . uniqid() . '.yaml';
        $this->createTestOpinionsFile();

        // Initialize Twig with inline template for macro testing
        $inlineTemplate = <<<TWIG
{% import "monster_card.twig" as mc %}
{{ mc.monster_card(monster) }}
TWIG;

        $arrayLoader = new \Twig\Loader\ArrayLoader([
            'inline.twig' => $inlineTemplate,
        ]);
        $fsLoader = new FilesystemLoader(__DIR__ . '/../../templates');
        $loader = new \Twig\Loader\ChainLoader([$arrayLoader, $fsLoader]);

        $this->twig = new Environment($loader, [
            'autoescape' => 'html',
            'strict_variables' => true,
            'cache' => false
        ]);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testOpinionsFile)) {
            unlink($this->testOpinionsFile);
        }
    }

    private function createTestOpinionsFile(): void
    {
        $content = <<<YAML
pikachu:
  opinion: "My sister caught a wild Pikachu in Viridian Forest in her version and I didn't get one until I got Yellow. Crazy. Of course its the most iconic Pokémon ever, but it's well deserved."
  rating: A
charizard:
  opinion: "Great fire-breathing dragon. Always been a fan."
  rating: S
YAML;
        file_put_contents($this->testOpinionsFile, $content);
    }

    public function test_opinion_display_in_template_when_opinion_exists(): void
    {
        //! @section Arrange
        $pokeApiService = new PokeApiService(function (string $url): string {
            if (str_contains($url, '/pokemon/pikachu')) {
                return json_encode([
                    'id' => 25,
                    'name' => 'pikachu',
                    'types' => [['slot' => 1, 'type' => ['name' => 'electric']]],
                    'sprites' => [
                        'other' => [
                            'official-artwork' => [
                                'front_default' => 'https://img.example/pikachu.png'
                            ]
                        ]
                    ]
                ]);
            }
            throw new \RuntimeException('Unexpected URL: ' . $url);
        });

        $opinionService = new PokemonOpinionService($this->testOpinionsFile);
        $presenter = new DexPresenter($pokeApiService, $opinionService, 300);

        //! @section Act
        $monsterData = $presenter->fetchMonsterData(MonsterIdentifier::fromString('pikachu'));
        $viewData = $presenter->present($monsterData);
        $html = $this->twig->render('inline.twig', ['monster' => $viewData['monster']]);

        //! @section Assert
        $this->assertStringContainsString('monster-card-opinion', $html);
        $this->assertStringContainsString('My sister caught a wild Pikachu', $html);
        $this->assertStringContainsString('monster-card-rating', $html);
        $this->assertStringContainsString('Rating: <span class="monster-card-rating-value">A</span>', $html);
    }

    public function test_no_opinion_display_when_opinion_does_not_exist(): void
    {
        //! @section Arrange
        $pokeApiService = new PokeApiService(function (string $url): string {
            if (str_contains($url, '/pokemon/bulbasaur')) {
                return json_encode([
                    'id' => 1,
                    'name' => 'bulbasaur',
                    'types' => [['slot' => 1, 'type' => ['name' => 'grass']]],
                    'sprites' => [
                        'other' => [
                            'official-artwork' => [
                                'front_default' => 'https://img.example/bulbasaur.png'
                            ]
                        ]
                    ]
                ]);
            }
            throw new \RuntimeException('Unexpected URL: ' . $url);
        });

        $opinionService = new PokemonOpinionService($this->testOpinionsFile);
        $presenter = new DexPresenter($pokeApiService, $opinionService, 300);

        //! @section Act
        $monsterData = $presenter->fetchMonsterData(MonsterIdentifier::fromString('bulbasaur'));
        $viewData = $presenter->present($monsterData);
        $html = $this->twig->render('inline.twig', ['monster' => $viewData['monster']]);

        //! @section Assert
        $this->assertStringNotContainsString('monster-card-opinion', $html);
        $this->assertStringNotContainsString('monster-card-rating', $html);
    }

    public function test_opinion_works_with_numeric_id(): void
    {
        //! @section Arrange
        $pokeApiService = new PokeApiService(function (string $url): string {
            if (str_contains($url, '/pokemon/25')) {
                return json_encode([
                    'id' => 25,
                    'name' => 'pikachu',
                    'types' => [['slot' => 1, 'type' => ['name' => 'electric']]],
                    'sprites' => [
                        'other' => [
                            'official-artwork' => [
                                'front_default' => 'https://img.example/pikachu.png'
                            ]
                        ]
                    ]
                ]);
            }
            throw new \RuntimeException('Unexpected URL: ' . $url);
        });

        $opinionService = new PokemonOpinionService($this->testOpinionsFile);
        $presenter = new DexPresenter($pokeApiService, $opinionService, 300);

        //! @section Act
        $monsterData = $presenter->fetchMonsterData(MonsterIdentifier::fromString('25'));
        $viewData = $presenter->present($monsterData);
        $html = $this->twig->render('inline.twig', ['monster' => $viewData['monster']]);

        //! @section Assert
        $this->assertStringContainsString('monster-card-opinion', $html);
        $this->assertStringContainsString('My sister caught a wild Pikachu', $html);
        $this->assertStringContainsString('Rating: <span class="monster-card-rating-value">A</span>', $html);
    }
}
