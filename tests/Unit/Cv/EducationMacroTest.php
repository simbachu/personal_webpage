<?php

declare(strict_types=1);

namespace Tests\Unit\Cv;

use PHPUnit\Framework\TestCase;
use Tests\Support\TwigTestFactory;
use Twig\Environment;

final class EducationMacroTest extends TestCase
{
    private Environment $twig;

    protected function setUp(): void
    {
        $this->twig = TwigTestFactory::createMacroEnvironment(<<<'TWIG'
{% import "@cv/education.twig" as edu %}
{{ edu.education(items) }}
TWIG);
    }

    public function test_macro_renders_education_with_optional_fields(): void
    {
        // Arrange
        $items = [[
            'institution' => 'Example Academy',
            'program' => 'Systems Developer',
            'from' => '2024-09',
            'to' => '2026-06',
            'highlights' => 'Top marks.',
            'skills' => ['C', 'C++'],
        ]];

        // Act
        $html = $this->twig->render('inline.twig', ['items' => $items]);

        // Assert
        $this->assertStringContainsString('<h2>Education</h2>', $html);
        $this->assertStringContainsString('<h3>Example Academy</h3>', $html);
        $this->assertStringContainsString('<h4>Systems Developer</h4>', $html);
        $this->assertStringContainsString('2024-09', $html);
        $this->assertStringContainsString('Top marks.', $html);
        $this->assertStringContainsString('class="skills"', $html);
        $this->assertStringContainsString('<li>C</li>', $html);
    }

    public function test_macro_omits_absent_optional_fields(): void
    {
        // Arrange
        $items = [[
            'institution' => 'Example University',
            'program' => 'Media Studies',
            'from' => '2013-09',
            'to' => '2016-06',
        ]];

        // Act
        $html = $this->twig->render('inline.twig', ['items' => $items]);

        // Assert
        $this->assertStringContainsString('Example University', $html);
        $this->assertStringNotContainsString('Top marks.', $html);
        $this->assertStringNotContainsString('class="skills"', $html);
    }
}
