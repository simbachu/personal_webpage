<?php

declare(strict_types=1);

namespace Tests\Unit\Cv;

use PHPUnit\Framework\TestCase;
use Tests\Support\TwigTestFactory;
use Twig\Environment;

final class HeaderMacroTest extends TestCase
{
    private Environment $twig;

    protected function setUp(): void
    {
        $this->twig = TwigTestFactory::createMacroEnvironment(<<<'TWIG'
{% import "@cv/header.twig" as header %}
{{ header.header(cv) }}
TWIG);
    }

    public function test_macro_renders_compact_contact_without_summary(): void
    {
        // Arrange
        $cv = [
            'name' => 'Ada Example',
            'email' => 'ada@example.test',
            'phone' => '+46-700-000000',
            'website' => 'https://www.example.test',
            'linkedin' => 'https://www.linkedin.com/in/ada/',
            'github' => 'https://github.com/ada',
            'summary' => 'This summary must not appear in the masthead.',
        ];

        // Act
        $html = $this->twig->render('inline.twig', ['cv' => $cv]);

        // Assert
        $this->assertStringContainsString('<h1>Ada Example</h1>', $html);
        $this->assertStringNotContainsString('This summary must not appear in the masthead.', $html);
        $this->assertStringNotContainsString('<dt>', $html);
        $this->assertStringContainsString('<a href="mailto:ada@example.test">ada@example.test</a>', $html);
        $this->assertStringContainsString('<a href="tel:+46-700-000000">+46-700-000000</a>', $html);
        $this->assertStringContainsString('<a href="https://www.example.test">www.example.test</a>', $html);
        $this->assertStringContainsString('<a href="https://www.linkedin.com/in/ada/">LinkedIn</a>', $html);
        $this->assertStringContainsString('<a href="https://github.com/ada">GitHub</a>', $html);
    }
}
