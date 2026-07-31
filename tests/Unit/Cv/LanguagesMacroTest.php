<?php

declare(strict_types=1);

namespace Tests\Unit\Cv;

use App\Cv\CvLabels;
use PHPUnit\Framework\TestCase;
use Tests\Support\TwigTestFactory;
use Twig\Environment;

final class LanguagesMacroTest extends TestCase
{
    private Environment $twig;

    protected function setUp(): void
    {
        $this->twig = TwigTestFactory::createMacroEnvironment(<<<'TWIG'
{% import "@cv/languages.twig" as lang %}
{{ lang.languages(items, labels) }}
TWIG);
    }

    public function test_macro_renders_language_with_level(): void
    {
        // Arrange
        $items = [[
            'language' => 'Swedish',
            'level' => 'Native',
        ]];

        // Act
        $html = $this->twig->render('inline.twig', [
            'items' => $items,
            'labels' => CvLabels::forLanguage('en'),
        ]);

        // Assert
        $this->assertStringContainsString('<h2>Languages</h2>', $html);
        $this->assertStringContainsString('class="languages"', $html);
        $this->assertStringNotContainsString('<dt>', $html);
        $this->assertStringContainsString('<strong>Swedish</strong>', $html);
        $this->assertStringContainsString('Native', $html);
    }

    public function test_macro_renders_optional_certificate(): void
    {
        // Arrange
        $items = [[
            'language' => 'English',
            'level' => 'Native-level',
            'certificate' => 'Cambridge C1',
        ]];

        // Act
        $html = $this->twig->render('inline.twig', [
            'items' => $items,
            'labels' => CvLabels::forLanguage('en'),
        ]);

        // Assert
        $this->assertStringContainsString('English', $html);
        $this->assertStringContainsString('(Cambridge C1)', $html);
    }

    public function test_macro_renders_swedish_section_title(): void
    {
        // Arrange
        $items = [[
            'language' => 'Svenska',
            'level' => 'Modersmål',
        ]];

        // Act
        $html = $this->twig->render('inline.twig', [
            'items' => $items,
            'labels' => CvLabels::forLanguage('sv'),
        ]);

        // Assert
        $this->assertStringContainsString('<h2>Språk</h2>', $html);
        $this->assertStringNotContainsString('<h2>Languages</h2>', $html);
    }
}
