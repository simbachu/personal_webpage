<?php

declare(strict_types=1);

namespace Tests\Unit\Cv;

use PHPUnit\Framework\TestCase;
use Tests\Support\TwigTestFactory;
use Twig\Environment;

final class CoverLetterTemplateTest extends TestCase
{
    private Environment $twig;

    protected function setUp(): void
    {
        $this->twig = TwigTestFactory::createEnvironment();
    }

    public function test_cover_letter_renders_header_and_paragraphs_without_cv_chrome(): void
    {
        // Arrange
        $cv = [
            'name' => 'Ada Example',
            'email' => 'ada@example.test',
            'phone' => '+46-700-000000',
            'website' => 'https://www.example.test',
            'linkedin' => 'https://www.linkedin.com/in/ada',
            'github' => 'https://github.com/ada',
            'language' => 'sv',
        ];
        $paragraphs = [
            'Hej Anders och Thomas.',
            'Jag söker er roll som systemutvecklare.',
            'Med vänliga hälsningar,',
            'Ada Example',
        ];

        // Act
        $html = $this->twig->render('@cv/cover-letter.twig', [
            'cv' => $cv,
            'paragraphs' => $paragraphs,
        ]);

        // Assert
        $this->assertStringContainsString('Ada Example', $html);
        $this->assertStringContainsString('ada@example.test', $html);
        $this->assertStringContainsString('<hr class="cv-anchor">', $html);
        $this->assertStringContainsString('class="cv-body cover-letter"', $html);
        $this->assertStringContainsString('<p>Hej Anders och Thomas.</p>', $html);
        $this->assertStringContainsString('<p>Jag söker er roll som systemutvecklare.</p>', $html);
        $this->assertStringContainsString('<p>Med vänliga hälsningar,</p>', $html);
        $this->assertStringContainsString('<p>Ada Example</p>', $html);
        $this->assertStringNotContainsString('<nav class="cv-language-switcher"', $html);
        $this->assertStringNotContainsString('This website was automatically uploaded', $html);
        $this->assertStringNotContainsString('class="cv-secondary"', $html);
    }

    public function test_cover_letter_inlines_cv_styles_and_letter_overrides(): void
    {
        // Arrange
        $cv = [
            'name' => 'Ada Example',
            'email' => 'ada@example.test',
            'phone' => '+46-700-000000',
            'website' => 'https://www.example.test',
            'language' => 'sv',
        ];

        // Act
        $html = $this->twig->render('@cv/cover-letter.twig', [
            'cv' => $cv,
            'paragraphs' => ['Hej.'],
        ]);

        // Assert
        $this->assertMatchesRegularExpression('/\.cv-body\s*\{[^}]*padding-left:\s*15mm/s', $html);
        $this->assertStringContainsString('.cv-body.cover-letter', $html);
        $this->assertMatchesRegularExpression(
            '/\.cv-body\.cover-letter\s*\{[^}]*line-height:\s*1\.45/s',
            $html
        );
    }

    public function test_cover_letter_preserves_soft_line_breaks_within_paragraphs(): void
    {
        // Arrange
        $cv = [
            'name' => 'Ada Example',
            'email' => 'ada@example.test',
            'phone' => '+46-700-000000',
            'website' => 'https://www.example.test',
            'language' => 'sv',
        ];

        // Act
        $html = $this->twig->render('@cv/cover-letter.twig', [
            'cv' => $cv,
            'paragraphs' => ["Med vänliga hälsningar,\nAda Example"],
        ]);

        // Assert
        $this->assertMatchesRegularExpression(
            '/<p>Med vänliga hälsningar,<br\s*\/?>\s*Ada Example<\/p>/',
            $html
        );
    }
}
