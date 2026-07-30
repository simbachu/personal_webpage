<?php

declare(strict_types=1);

namespace Tests\Unit\Cv;

use PHPUnit\Framework\TestCase;
use Tests\Support\CvFixture;
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
        $cv = CvFixture::cv();

        // Act
        $html = $this->twig->render('@cv/cv.twig', ['cv' => $cv]);

        // Assert
        $this->assertStringContainsString('<hr class="cv-anchor">', $html);
        $this->assertStringContainsString('<div class="cv-body">', $html);
        $this->assertMatchesRegularExpression(
            '/<hr class="cv-anchor">\s*<div class="cv-body">\s*<p>Systems developer with a background in information design/',
            $html
        );
        $this->assertStringContainsString('</div>', $html);
        $this->assertTrue(
            strpos($html, 'class="cv-body"') < strpos($html, '<article>'),
            'cv-body should wrap the article'
        );
    }
}
