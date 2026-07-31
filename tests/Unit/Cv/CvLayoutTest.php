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

    public function test_cv_template_inlines_body_gutter_and_experience_rhythm(): void
    {
        // Arrange
        $cv = $this->minimalCv();

        // Act
        $html = $this->twig->render('@cv/cv.twig', ['cv' => $cv]);

        // Assert — presence of layout contracts, not exact nested-CSS source shape
        $this->assertMatchesRegularExpression('/\.cv-body\s*\{[^}]*padding-left:\s*15mm/s', $html);
        $this->assertStringContainsString('border-top: 0.8pt solid #111;', $html);
        $this->assertStringContainsString('font-variant-numeric: tabular-nums;', $html);
        $this->assertMatchesRegularExpression('/& article\s*\{[^}]*margin-top:\s*0\.65em/s', $html);
    }

    public function test_cv_template_includes_nested_header_and_list_style_hooks(): void
    {
        // Arrange
        $cv = $this->minimalCv();

        // Act
        $html = $this->twig->render('@cv/cv.twig', ['cv' => $cv]);

        // Assert
        $this->assertStringContainsString('&.skills,', $html);
        $this->assertStringContainsString('&.languages', $html);
        $this->assertStringContainsString('& > header', $html);
        $this->assertMatchesRegularExpression('/& a\s*\{[^}]*display:\s*grid/s', $html);
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

    public function test_english_cv_links_to_swedish_with_accessible_flag_toggle(): void
    {
        // Arrange
        $cv = $this->minimalCv(['language' => 'en']);

        // Act
        $html = $this->twig->render('@cv/cv.twig', ['cv' => $cv]);

        // Assert
        $this->assertStringContainsString('class="cv-language-switcher"', $html);
        $this->assertStringContainsString('href="/cv?lang=sv"', $html);
        $this->assertStringContainsString('hreflang="sv"', $html);
        $this->assertStringContainsString('aria-label="Visa CV på svenska"', $html);
        $this->assertStringContainsString('🇸🇪', $html);
    }

    public function test_swedish_cv_links_to_english_with_accessible_flag_toggle(): void
    {
        // Arrange
        $cv = $this->minimalCv(['language' => 'sv']);

        // Act
        $html = $this->twig->render('@cv/cv.twig', ['cv' => $cv]);

        // Assert
        $this->assertStringContainsString('href="/cv?lang=en"', $html);
        $this->assertStringContainsString('hreflang="en"', $html);
        $this->assertStringContainsString('aria-label="View CV in English"', $html);
        $this->assertStringContainsString('🇬🇧', $html);
    }

    public function test_language_switcher_is_fixed_on_screen_and_hidden_for_print(): void
    {
        // Arrange
        $cv = $this->minimalCv();

        // Act
        $html = $this->twig->render('@cv/cv.twig', ['cv' => $cv]);

        // Assert
        $this->assertMatchesRegularExpression(
            '/\.cv-language-switcher\s*\{[^}]*@media screen\s*\{[^}]*position:\s*fixed/s',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/\.cv-language-switcher\s*\{[\s\S]*?@media print\s*\{[^}]*display:\s*none/s',
            $html
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
