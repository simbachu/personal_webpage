<?php

declare(strict_types=1);

namespace Tests\Unit\Cv;

use PHPUnit\Framework\TestCase;
use Tests\Support\CvFixture;
use Tests\Support\TwigTestFactory;
use Twig\Environment;

final class ExperienceMacroTest extends TestCase
{
    private Environment $twig;

    protected function setUp(): void
    {
        $this->twig = TwigTestFactory::createMacroEnvironment(<<<'TWIG'
{% import "@cv/experience.twig" as exp %}
{{ exp.experience(items) }}
TWIG, ['strict_variables' => true]);
    }

    public function test_macro_renders_full_experience_list_under_strict_variables(): void
    {
        // Arrange
        $items = CvFixture::experience();

        // Act
        $html = $this->twig->render('inline.twig', ['items' => $items]);

        // Assert
        $this->assertStringContainsString('<h2>Experience</h2>', $html);
        $this->assertStringContainsString('Berg Propulsion', $html);
        $this->assertStringContainsString('Volvo Buses', $html);
        $this->assertStringContainsString('Swedish Armed Forces', $html);
        $this->assertStringContainsString('Earlier experience', $html);
    }

    public function test_macro_renders_company_with_roles(): void
    {
        // Arrange
        $items = [CvFixture::experienceByCompany('Berg Propulsion')];

        // Act
        $html = $this->twig->render('inline.twig', ['items' => $items]);

        // Assert
        $this->assertStringContainsString('<h2>Experience</h2>', $html);
        $this->assertStringContainsString('<header>', $html);
        $this->assertStringContainsString('<h3>Berg Propulsion</h3>', $html);
        $this->assertStringContainsString('Hönö och Öckerö', $html);
        $this->assertStringContainsString('<h4>Student Intern</h4>', $html);
        $this->assertStringContainsString('2025-11', $html);
        $this->assertStringContainsString('2026-06', $html);
        $this->assertStringContainsString('Go &amp; React intern on maritime Energy Management System', $html);
        $this->assertStringContainsString('Go', $html);
        $this->assertStringContainsString('React', $html);
    }

    public function test_macro_renders_null_to_date_as_present(): void
    {
        // Arrange
        $items = [CvFixture::experienceByCompany('Volvo Buses')];

        // Act
        $html = $this->twig->render('inline.twig', ['items' => $items]);

        // Assert
        $this->assertStringContainsString('Volvo Buses', $html);
        $this->assertStringContainsString('6 yrs 7 mos', $html);
        $this->assertStringContainsString('Team Leader', $html);
        $this->assertStringContainsString('2024-04', $html);
        $this->assertStringContainsString('Present', $html);
        $this->assertStringContainsString('Team leader for Team Magma', $html);
    }

    public function test_macro_renders_section_blurb(): void
    {
        // Arrange
        $items = [CvFixture::experienceSection()];

        // Act
        $html = $this->twig->render('inline.twig', ['items' => $items]);

        // Assert
        $this->assertStringContainsString('Earlier experience', $html);
        $this->assertStringContainsString('Security guard at Cubsec AB', $html);
    }

    public function test_macro_renders_organization_entry(): void
    {
        // Arrange
        $items = [CvFixture::experienceByOrganization('Swedish Armed Forces')];

        // Act
        $html = $this->twig->render('inline.twig', ['items' => $items]);

        // Assert
        $this->assertStringContainsString('Swedish Armed Forces', $html);
        $this->assertStringContainsString('Halmstad', $html);
        $this->assertStringContainsString('Insatssoldat Lv6', $html);
        $this->assertStringContainsString('Facility protection and escort duty', $html);
    }

    public function test_macro_renders_role_bullets(): void
    {
        // Arrange
        $items = [CvFixture::experienceByCompany('Volvo Buses')];

        // Act
        $html = $this->twig->render('inline.twig', ['items' => $items]);

        // Assert
        $this->assertStringContainsString('Owned processes, roadmaps, and continuous improvement', $html);
        $this->assertStringContainsString('Agile coach for the international aftermarket department', $html);
    }
}
