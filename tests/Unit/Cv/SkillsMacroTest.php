<?php

declare(strict_types=1);

namespace Tests\Unit\Cv;

use PHPUnit\Framework\TestCase;
use Tests\Support\CvFixture;
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

    public function test_macro_renders_skill_groups_and_highlights(): void
    {
        // Arrange
        $skills = CvFixture::skills();
        $highlights = CvFixture::skillHighlights();

        // Act
        $html = $this->twig->render('inline.twig', [
            'skills' => $skills,
            'highlights' => $highlights,
        ]);

        // Assert
        $this->assertStringContainsString('<h2>Skills</h2>', $html);
        $this->assertStringContainsString('programming languages', $html);
        $this->assertStringContainsString('C', $html);
        $this->assertStringContainsString('C++', $html);
        $this->assertStringContainsString('Go', $html);
        $this->assertStringContainsString('web', $html);
        $this->assertStringContainsString('HTMX', $html);
        $this->assertStringContainsString('React', $html);
        $this->assertStringContainsString('Server-side rendered web UIs with HTMX', $html);
        $this->assertStringContainsString('CI/CD with GitHub Actions', $html);
    }
}
