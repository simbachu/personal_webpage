<?php

declare(strict_types=1);

namespace Tests\Unit\Cv;

use App\Cv\CvLabels;
use PHPUnit\Framework\TestCase;
use Tests\Support\TwigTestFactory;
use Twig\Environment;

final class ExperienceMacroTest extends TestCase
{
    private Environment $twig;

    protected function setUp(): void
    {
        $this->twig = TwigTestFactory::createMacroEnvironment(<<<'TWIG'
{% import "@cv/experience.twig" as exp %}
{{ exp.experience(items, labels) }}
TWIG, ['strict_variables' => true]);
    }

    public function test_macro_renders_company_role_with_inline_meta(): void
    {
        // Arrange
        $items = [[
            'company' => 'Acme Corp',
            'location' => 'Gothenburg',
            'roles' => [[
                'position' => 'Engineer',
                'from' => '2024-01',
                'to' => '2024-06',
                'duration' => '6 mos',
                'employment' => 'Internship',
                'summary' => 'Built a test harness.',
            ]],
        ]];

        // Act
        $html = $this->twig->render('inline.twig', [
            'items' => $items,
            'labels' => CvLabels::forLanguage('en'),
        ]);

        // Assert
        $this->assertStringContainsString('<h2>Experience</h2>', $html);
        $this->assertStringContainsString('<h3>Acme Corp</h3>', $html);
        $this->assertStringContainsString('<span class="meta">Gothenburg</span>', $html);
        $this->assertStringContainsString('<h4>Engineer</h4>', $html);
        $this->assertStringContainsString('<span class="meta">Internship</span>', $html);
        $this->assertStringContainsString('2024-01', $html);
        $this->assertStringContainsString('2024-06', $html);
        $this->assertStringContainsString('Built a test harness.', $html);
        $this->assertStringNotContainsString('<p><span>', $html);
    }

    public function test_macro_renders_null_to_date_as_present(): void
    {
        // Arrange
        $items = [[
            'company' => 'Acme Corp',
            'tenure' => '2 yrs',
            'roles' => [[
                'position' => 'Lead',
                'from' => '2024-04',
                'to' => null,
                'summary' => 'Leading the team.',
            ]],
        ]];

        // Act
        $html = $this->twig->render('inline.twig', [
            'items' => $items,
            'labels' => CvLabels::forLanguage('en'),
        ]);

        // Assert
        $this->assertStringContainsString('class="tenure"', $html);
        $this->assertStringContainsString('2 yrs', $html);
        $this->assertStringContainsString('Present', $html);
    }

    public function test_macro_renders_section_blurb(): void
    {
        // Arrange
        $items = [[
            'section' => 'Earlier',
            'summary' => 'Assorted prior roles.',
        ]];

        // Act
        $html = $this->twig->render('inline.twig', [
            'items' => $items,
            'labels' => CvLabels::forLanguage('en'),
        ]);

        // Assert
        $this->assertStringContainsString('<h3>Earlier</h3>', $html);
        $this->assertStringContainsString('Assorted prior roles.', $html);
    }

    public function test_macro_renders_organization_entry(): void
    {
        // Arrange
        $items = [[
            'organization' => 'Example Forces',
            'location' => 'Halmstad',
            'roles' => [[
                'position' => 'Conscripteer',
                'from' => '2005-08',
                'to' => '2006-06',
                'summary' => 'Duty summary.',
            ]],
        ]];

        // Act
        $html = $this->twig->render('inline.twig', [
            'items' => $items,
            'labels' => CvLabels::forLanguage('en'),
        ]);

        // Assert
        $this->assertStringContainsString('<h3>Example Forces</h3>', $html);
        $this->assertStringContainsString('Halmstad', $html);
        $this->assertStringContainsString('<h4>Conscripteer</h4>', $html);
    }

    public function test_macro_renders_role_bullets_and_skills(): void
    {
        // Arrange
        $items = [[
            'company' => 'Acme Corp',
            'roles' => [[
                'position' => 'Engineer',
                'from' => '2022-01',
                'to' => '2023-01',
                'bullets' => ['Shipped the feature.'],
                'skills' => ['Go', 'React'],
            ]],
        ]];

        // Act
        $html = $this->twig->render('inline.twig', [
            'items' => $items,
            'labels' => CvLabels::forLanguage('en'),
        ]);

        // Assert
        $this->assertStringContainsString('<li>Shipped the feature.</li>', $html);
        $this->assertStringContainsString('class="skills"', $html);
        $this->assertStringContainsString('<li>Go</li>', $html);
        $this->assertStringContainsString('<li>React</li>', $html);
    }

    public function test_macro_renders_swedish_section_title_and_present(): void
    {
        // Arrange
        $items = [[
            'company' => 'Acme Corp',
            'roles' => [[
                'position' => 'Lead',
                'from' => '2024-04',
                'to' => null,
                'summary' => 'Leder teamet.',
            ]],
        ]];

        // Act
        $html = $this->twig->render('inline.twig', [
            'items' => $items,
            'labels' => CvLabels::forLanguage('sv'),
        ]);

        // Assert
        $this->assertStringContainsString('<h2>Erfarenhet</h2>', $html);
        $this->assertStringContainsString('Nuvarande', $html);
        $this->assertStringNotContainsString('<h2>Experience</h2>', $html);
        $this->assertStringNotContainsString('Present', $html);
    }
}
