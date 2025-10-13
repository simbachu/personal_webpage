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
}


