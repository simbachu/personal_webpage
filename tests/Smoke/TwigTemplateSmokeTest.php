<?php

declare(strict_types=1);

namespace Tests\Smoke;

use PHPUnit\Framework\TestCase;
use Tests\TestData\PokemonTestData;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

//! @brief Smoke test for Twig template rendering with Pokemon data
//!
//! Tests that Twig templates can render Pokemon data correctly without API calls
class TwigTemplateSmokeTest extends TestCase
{
    private Environment $twig; //!< Twig environment for template rendering

    //! @brief Set up Twig environment before each test
    protected function setUp(): void
    {
        $loader = new FilesystemLoader(__DIR__ . '/../../templates');
        $this->twig = new Environment($loader, [
            'cache' => false,
            'debug' => false,
        ]);
    }

    public function testMonsterCardRendersDualTypePokemon(): void
    {
        //! @section Arrange
        $monster = PokemonTestData::getBulbasaur();
        $viewData = [
            'template' => 'dex',
            'monster' => $monster,
        ];

        //! @section Act
        $rendered = $this->twig->render('dex.twig', $viewData);

        //! @section Assert
        $this->assertStringContainsString('Bulbasaur', $rendered);
        $this->assertStringContainsString('#1', $rendered);
        $this->assertStringContainsString('grass', $rendered);
        $this->assertStringContainsString('poison', $rendered);
        $this->assertStringContainsString('data-type1="grass"', $rendered);
        $this->assertStringContainsString('data-type2="poison"', $rendered);

        //! Ensure both types are rendered as list items
        $this->assertEquals(1, substr_count($rendered, 'class="type type-grass"'));
        $this->assertEquals(1, substr_count($rendered, 'class="type type-poison"'));

        //! Ensure template structure is correct
        $this->assertStringContainsString('<article class="monster-card"', $rendered);
        $this->assertStringContainsString('<ul class="monster-types">', $rendered);
        $this->assertStringContainsString('</ul>', $rendered);
    }

    public function testMonsterCardRendersSingleTypePokemon(): void
    {
        //! @section Arrange
        $monster = PokemonTestData::getCharmander();
        $viewData = [
            'template' => 'dex',
            'monster' => $monster,
        ];

        //! @section Act
        $rendered = $this->twig->render('dex.twig', $viewData);

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

        //! Ensure template structure is correct
        $this->assertStringContainsString('<article class="monster-card"', $rendered);
        $this->assertStringContainsString('<ul class="monster-types">', $rendered);
        $this->assertStringContainsString('</ul>', $rendered);
    }

    public function testMonsterCardHandlesMissingType2(): void
    {
        //! @section Arrange
        $monster = PokemonTestData::getCharmander();
        //! Remove type2 key to simulate service not adding it
        unset($monster['type2']);

        $viewData = [
            'template' => 'dex',
            'monster' => $monster,
        ];

        //! @section Act
        $rendered = $this->twig->render('dex.twig', $viewData);

        //! @section Assert
        $this->assertStringContainsString('Charmander', $rendered);
        $this->assertStringContainsString('data-type1="fire"', $rendered);
        $this->assertStringContainsString('data-type2=""', $rendered);

        //! Ensure only one type is rendered
        $this->assertEquals(1, substr_count($rendered, 'class="type type-fire"'));

        //! Template should not crash when type2 is missing
        $this->assertStringNotContainsString('type-grass', $rendered);
    }

    public function testMonsterCardHandlesEmptyTypes(): void
    {
        //! @section Arrange
        $monster = PokemonTestData::getTypelessPokemon();
        $viewData = [
            'template' => 'dex',
            'monster' => $monster,
        ];

        //! @section Act
        $rendered = $this->twig->render('dex.twig', $viewData);

        //! @section Assert
        $this->assertStringContainsString('Unknown', $rendered);
        $this->assertStringContainsString('#999', $rendered);
        $this->assertStringContainsString('data-type1=""', $rendered);
        $this->assertStringContainsString('data-type2=""', $rendered);

        //! Should not render any type classes when types are empty
        $this->assertStringNotContainsString('class="type type-', $rendered);

        //! Template should not crash with empty types
        $this->assertStringContainsString('<ul class="monster-types">', $rendered);
        $this->assertStringContainsString('</ul>', $rendered);
    }

    public function testTemplateStructureIsValid(): void
    {
        //! @section Arrange
        $monster = PokemonTestData::getBulbasaur();
        $viewData = [
            'template' => 'dex',
            'monster' => $monster,
        ];

        //! @section Act
        $rendered = $this->twig->render('dex.twig', $viewData);

        //! @section Assert
        //! Check for proper HTML structure
        $this->assertStringContainsString('<!DOCTYPE html>', $rendered);
        $this->assertStringContainsString('<html lang="en">', $rendered);
        $this->assertStringContainsString('<head>', $rendered);
        $this->assertStringContainsString('<body>', $rendered);
        $this->assertStringContainsString('<main>', $rendered);
        $this->assertStringContainsString('<footer>', $rendered);

        //! Check for proper monster card structure
        $this->assertStringContainsString('<article class="monster-card"', $rendered);
        $this->assertStringContainsString('<header class="monster-card-header">', $rendered);
        $this->assertStringContainsString('<h1 class="monster-card-title">', $rendered);
        $this->assertStringContainsString('<div class="monster-card-body">', $rendered);
        $this->assertStringContainsString('<figure class="monster-card-image">', $rendered);
        $this->assertStringContainsString('<div class="monster-card-info">', $rendered);

        //! Check for proper image structure
        $this->assertStringContainsString('<img src="https://img.example/bulbasaur.png"', $rendered);
        $this->assertStringContainsString('alt="Bulbasaur"', $rendered);
    }

    public function testDexTemplateRendersWithTierlist(): void
    {
        //! @section Arrange
        $tierlist = [
            'name' => 'Test Tierlist',
            'tiers' => [
                [
                    'name' => 'A',
                    'monsters' => [
                        ['name' => 'Eevee', 'sprite_image' => 'https://img.example/eevee.png', 'url' => '/dex/eevee']
                    ]
                ]
            ]
        ];

        //! @section Act
        $rendered = $this->twig->render('dex.twig', [
            'tierlist' => $tierlist,
            'meta' => ['title' => 'Tierlist']
        ]);

        //! @section Assert
        $this->assertStringContainsString('Test Tierlist', $rendered);
        $this->assertStringContainsString('/dex/eevee', $rendered);
        $this->assertStringContainsString('<img src="https://img.example/eevee.png"', $rendered);
    }
}
