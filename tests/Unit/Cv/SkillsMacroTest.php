<?php

declare(strict_types=1);

namespace Tests\Unit\Cv;

use PHPUnit\Framework\TestCase;
use Tests\Support\TwigTestFactory;
use Twig\Environment;

final class SkillsMacroTest extends TestCase
{
    private Environment $twig;

    protected function setUp(): void
    {
        $this->twig = TwigTestFactory::createMacroEnvironment(<<<'TWIG'
{% import "@cv/skills.twig" as sk %}
{{ sk.skills(skills, highlights) }}
TWIG);
    }

    public function test_macro_renders_compact_skill_groups(): void
    {
        // Arrange
        $skills = [
            'programming_languages' => ['C', 'Go'],
            'web' => ['HTMX'],
        ];

        // Act
        $html = $this->twig->render('inline.twig', [
            'skills' => $skills,
            'highlights' => [],
        ]);

        // Assert
        $this->assertStringContainsString('<h2>Skills</h2>', $html);
        $this->assertStringContainsString('class="skill-groups"', $html);
        $this->assertStringContainsString('class="skill-group-label"', $html);
        $this->assertStringContainsString('programming languages', $html);
        $this->assertStringContainsString('class="skills"', $html);
        $this->assertStringContainsString('<li>C</li>', $html);
        $this->assertStringContainsString('<li>Go</li>', $html);
        $this->assertStringContainsString('web', $html);
        $this->assertStringContainsString('<li>HTMX</li>', $html);
    }

    public function test_macro_renders_optional_highlights_when_present(): void
    {
        // Arrange
        $skills = ['web' => ['HTMX']];
        $highlights = ['Built SSR UIs with HTMX.'];

        // Act
        $html = $this->twig->render('inline.twig', [
            'skills' => $skills,
            'highlights' => $highlights,
        ]);

        // Assert
        $this->assertStringContainsString('Built SSR UIs with HTMX.', $html);
    }
}
