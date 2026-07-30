<?php

declare(strict_types=1);

namespace Tests\Unit\Cv;

use PHPUnit\Framework\TestCase;
use Tests\Support\CvFixture;
use Tests\Support\TwigTestFactory;
use Twig\Environment;

final class LanguagesMacroTest extends TestCase
{
    private Environment $twig;

    protected function setUp(): void
    {
        $this->twig = TwigTestFactory::createMacroEnvironment(<<<'TWIG'
{% import "@cv/languages.twig" as lang %}
{{ lang.languages(items) }}
TWIG);
    }

    public function test_macro_renders_language_with_level(): void
    {
        // Arrange
        $items = [CvFixture::languageByName('Swedish')];

        // Act
        $html = $this->twig->render('inline.twig', ['items' => $items]);

        // Assert
        $this->assertStringContainsString('<h2>Languages</h2>', $html);
        $this->assertStringContainsString('Swedish', $html);
        $this->assertStringContainsString('Native', $html);
    }

    public function test_macro_renders_language_with_optional_certificate(): void
    {
        // Arrange
        $items = [CvFixture::languageByName('English')];

        // Act
        $html = $this->twig->render('inline.twig', ['items' => $items]);

        // Assert
        $this->assertStringContainsString('English', $html);
        $this->assertStringContainsString('Native-level', $html);
        $this->assertStringContainsString('Cambridge C1 Advanced, grade A', $html);
    }
}
