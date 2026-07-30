<?php

declare(strict_types=1);

namespace Tests\Unit\Cv;

use PHPUnit\Framework\TestCase;
use Tests\Support\CvFixture;
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

    public function test_macro_renders_education_with_highlights_and_skills(): void
    {
        // Arrange
        $items = [CvFixture::educationByInstitution('Chas Academy')];

        // Act
        $html = $this->twig->render('inline.twig', ['items' => $items]);

        // Assert
        $this->assertStringContainsString('<h2>Education</h2>', $html);
        $this->assertStringContainsString('<header>', $html);
        $this->assertStringContainsString('<h3>Chas Academy</h3>', $html);
        $this->assertStringContainsString('<h4>Systems Developer C/C++</h4>', $html);
        $this->assertStringContainsString('2024-09', $html);
        $this->assertStringContainsString('2026-06', $html);
        $this->assertStringContainsString('Top marks.', $html);
        $this->assertStringContainsString('class="skills"', $html);
        $this->assertStringContainsString('C', $html);
        $this->assertStringContainsString('C++', $html);
        $this->assertStringContainsString('Unit Testing', $html);
    }

    public function test_macro_renders_education_without_optional_highlights(): void
    {
        // Arrange
        $items = [CvFixture::educationByInstitution('Linnaeus University')];

        // Act
        $html = $this->twig->render('inline.twig', ['items' => $items]);

        // Assert
        $this->assertStringContainsString('Linnaeus University', $html);
        $this->assertStringContainsString('Communication and Media Studies', $html);
        $this->assertStringNotContainsString('Top marks.', $html);
        $this->assertStringContainsString('Layout Design', $html);
    }
}
