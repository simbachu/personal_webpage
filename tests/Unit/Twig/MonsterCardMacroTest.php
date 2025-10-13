<?php

declare(strict_types=1);

namespace Tests\Unit\Twig;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Loader\ChainLoader;
use Twig\Loader\FilesystemLoader;

final class MonsterCardMacroTest extends TestCase
{
    private Environment $twig;

    protected function setUp(): void
    {
        $inlineTemplate = <<<'TWIG'
{% import "monster_card.twig" as mc %}
{{ mc.monster_card(monster) }}
TWIG;

        $arrayLoader = new ArrayLoader([
            'inline.twig' => $inlineTemplate,
        ]);
        $fsLoader = new FilesystemLoader(__DIR__ . '/../../../templates');
        $loader = new ChainLoader([$arrayLoader, $fsLoader]);

        $this->twig = new Environment($loader, [
            'cache' => false,
            'debug' => false,
        ]);
    }

    public function test_macro_renders_dual_type_monster(): void
    {
        // Arrange
        $monster = [
            'id' => 1,
            'name' => 'Bulbasaur',
            'image' => 'https://img.example/bulbasaur.png',
            'type1' => 'grass',
            'type2' => 'poison',
        ];

        // Act
        $html = $this->twig->render('inline.twig', ['monster' => $monster]);

        // Assert
        $this->assertStringContainsString('data-type1="grass"', $html);
        $this->assertStringContainsString('data-type2="poison"', $html);
        $this->assertStringContainsString('class="type type-grass"', $html);
        $this->assertStringContainsString('class="type type-poison"', $html);
    }

    public function test_macro_renders_single_type_monster_when_type2_missing(): void
    {
        // Arrange
        $monster = [
            'id' => 4,
            'name' => 'Charmander',
            'image' => 'https://img.example/charmander.png',
            'type1' => 'fire',
            // type2 intentionally omitted
        ];

        // Act
        $html = $this->twig->render('inline.twig', ['monster' => $monster]);

        // Assert
        $this->assertStringContainsString('data-type1="fire"', $html);
        $this->assertStringContainsString('data-type2=""', $html);
        $this->assertEquals(1, substr_count($html, 'class="type type-fire"'));
        $this->assertStringNotContainsString('type-poison', $html);
    }

    public function test_macro_renders_single_type_monster_when_type2_empty_string(): void
    {
        // Arrange
        $monster = [
            'id' => 25,
            'name' => 'Pikachu',
            'image' => 'https://img.example/pikachu.png',
            'type1' => 'electric',
            'type2' => '',
        ];

        // Act
        $html = $this->twig->render('inline.twig', ['monster' => $monster]);

        // Assert
        $this->assertStringContainsString('data-type1="electric"', $html);
        $this->assertStringContainsString('data-type2=""', $html);
        $this->assertEquals(1, substr_count($html, 'class="type type-electric"'));
        $this->assertStringNotContainsString('type-flying', $html);
    }

    public function test_macro_renders_ditto_with_no_evolution_links(): void
    {
        // Arrange
        $monster = [
            'id' => 132,
            'name' => 'Ditto',
            'image' => 'https://img.example/ditto.png',
            'type1' => 'normal',
            // No precursor or successor
        ];

        // Act
        $html = $this->twig->render('inline.twig', ['monster' => $monster]);

        // Assert
        $this->assertStringContainsString('Ditto', $html);
        $this->assertStringContainsString('data-type1="normal"', $html);
        $this->assertStringNotContainsString('monster-card-links', $html);
        $this->assertStringNotContainsString('From:', $html);
        $this->assertStringNotContainsString('To:', $html);
    }

    public function test_macro_renders_bulbasaur_with_successor_link(): void
    {
        // Arrange
        $monster = [
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

        // Act
        $html = $this->twig->render('inline.twig', ['monster' => $monster]);

        // Assert
        $this->assertStringContainsString('Bulbasaur', $html);
        $this->assertStringContainsString('monster-card-links', $html);
        $this->assertStringContainsString('To:', $html);
        $this->assertStringContainsString('<a href="/dex/2">Ivysaur</a>', $html);
        $this->assertStringNotContainsString('From:', $html);
    }

    public function test_macro_renders_venusaur_with_precursor_link(): void
    {
        // Arrange
        $monster = [
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

        // Act
        $html = $this->twig->render('inline.twig', ['monster' => $monster]);

        // Assert
        $this->assertStringContainsString('Venusaur', $html);
        $this->assertStringContainsString('monster-card-links', $html);
        $this->assertStringContainsString('From:', $html);
        $this->assertStringContainsString('<a href="/dex/2">Ivysaur</a>', $html);
        $this->assertStringNotContainsString('To:', $html);
    }

    public function test_macro_renders_monster_with_both_evolution_links(): void
    {
        // Arrange
        $monster = [
            'id' => 2,
            'name' => 'Ivysaur',
            'image' => 'https://img.example/ivysaur.png',
            'type1' => 'grass',
            'type2' => 'poison',
            'precursor' => [
                'name' => 'Bulbasaur',
                'url' => '/dex/1'
            ],
            'successor' => [
                'name' => 'Venusaur',
                'url' => '/dex/3'
            ]
        ];

        // Act
        $html = $this->twig->render('inline.twig', ['monster' => $monster]);

        // Assert
        $this->assertStringContainsString('Ivysaur', $html);
        $this->assertStringContainsString('monster-card-links', $html);
        $this->assertStringContainsString('From:', $html);
        $this->assertStringContainsString('To:', $html);
        $this->assertStringContainsString('<a href="/dex/1">Bulbasaur</a>', $html);
        $this->assertStringContainsString('<a href="/dex/3">Venusaur</a>', $html);
    }

    public function test_macro_renders_eevee_with_multiple_evolutions(): void
    {
        // Arrange
        $monster = [
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

        // Act
        $html = $this->twig->render('inline.twig', ['monster' => $monster]);

        // Assert
        $this->assertStringContainsString('Eevee', $html);
        $this->assertStringContainsString('monster-card-links', $html);
        $this->assertStringContainsString('To:', $html);
        $this->assertStringContainsString('evolution-list', $html);

        // Check that all evolution links are present
        $this->assertStringContainsString('<a href="/dex/vaporeon">Vaporeon</a>', $html);
        $this->assertStringContainsString('<a href="/dex/jolteon">Jolteon</a>', $html);
        $this->assertStringContainsString('<a href="/dex/flareon">Flareon</a>', $html);
        $this->assertStringContainsString('<a href="/dex/espeon">Espeon</a>', $html);
        $this->assertStringContainsString('<a href="/dex/umbreon">Umbreon</a>', $html);
        $this->assertStringContainsString('<a href="/dex/leafeon">Leafeon</a>', $html);
        $this->assertStringContainsString('<a href="/dex/glaceon">Glaceon</a>', $html);
        $this->assertStringContainsString('<a href="/dex/sylveon">Sylveon</a>', $html);

        // Should not have single successor
        $this->assertStringNotContainsString('successor', $html);
    }
}


