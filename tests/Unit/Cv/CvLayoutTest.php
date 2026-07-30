<?php

declare(strict_types=1);

namespace Tests\Unit\Cv;

use PHPUnit\Framework\TestCase;
use Tests\Support\TwigTestFactory;
use Twig\Environment;

final class CvLayoutTest extends TestCase
{
    private Environment $twig;

    protected function setUp(): void
    {
        $this->twig = TwigTestFactory::createEnvironment();
    }

    public function test_cv_template_places_summary_below_anchor_in_indented_body(): void
    {
        // Arrange
        $cv = $this->minimalCv();

        // Act
        $html = $this->twig->render('@cv/cv.twig', ['cv' => $cv]);

        // Assert
        $this->assertStringContainsString('<hr class="cv-anchor">', $html);
        $this->assertStringContainsString('<div class="cv-body">', $html);
        $this->assertMatchesRegularExpression(
            '/<hr class="cv-anchor">\s*<div class="cv-body">\s*<p>Short summary for layout\.<\/p>/',
            $html
        );
        $this->assertStringNotContainsString('Short summary for layout.', explode('<hr class="cv-anchor">', $html)[0]);
    }

    public function test_cv_template_orders_skills_before_experience_for_swedish_scan(): void
    {
        // Arrange
        $cv = $this->minimalCv([
            'experience' => [[
                'company' => 'Acme',
                'roles' => [[
                    'position' => 'Dev',
                    'from' => '2024-01',
                    'to' => '2024-06',
                    'summary' => 'Worked.',
                ]],
            ]],
            'skills' => ['languages' => ['Go']],
            'education' => [[
                'institution' => 'Example Uni',
                'program' => 'CS',
                'from' => '2010-01',
                'to' => '2013-01',
            ]],
        ]);

        // Act
        $html = $this->twig->render('@cv/cv.twig', ['cv' => $cv]);

        // Assert
        $skillsPos = strpos($html, '<h2>Skills</h2>');
        $experiencePos = strpos($html, '<h2>Experience</h2>');
        $educationPos = strpos($html, '<h2>Education</h2>');
        $this->assertNotFalse($skillsPos);
        $this->assertNotFalse($experiencePos);
        $this->assertNotFalse($educationPos);
        $this->assertTrue($skillsPos < $experiencePos, 'Skills should precede Experience');
        $this->assertTrue($experiencePos < $educationPos, 'Experience should precede Education');
        $this->assertStringContainsString('class="cv-secondary"', $html);
        $this->assertTrue(
            strpos($html, 'class="cv-secondary"') < $educationPos,
            'Education should live in the secondary block'
        );
    }

    //! @param overrides Extra CV fields merged into a minimal view model
    //! @return array<string, mixed>
    private function minimalCv(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Ada Example',
            'email' => 'ada@example.test',
            'phone' => '+46-700-000000',
            'website' => 'https://www.example.test',
            'language' => 'en',
            'summary' => 'Short summary for layout.',
            'experience' => [],
            'education' => [],
            'certificates' => [],
            'languages' => [],
            'skills' => [],
            'skill_highlights' => [],
        ], $overrides);
    }
}
