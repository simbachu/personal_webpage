<?php

declare(strict_types=1);

namespace Tests\Unit\Cv;

use PHPUnit\Framework\TestCase;
use Tests\Support\CvFixture;
use Tests\Support\TwigTestFactory;
use Twig\Environment;

final class CertificatesMacroTest extends TestCase
{
    private Environment $twig;

    protected function setUp(): void
    {
        $this->twig = TwigTestFactory::createMacroEnvironment(<<<'TWIG'
{% import "@cv/certificates.twig" as cert %}
{{ cert.certificates(items) }}
TWIG);
    }

    public function test_macro_renders_certificate_with_grade(): void
    {
        // Arrange
        $items = [CvFixture::certificateByName('C1 Advanced')];

        // Act
        $html = $this->twig->render('inline.twig', ['items' => $items]);

        // Assert
        $this->assertStringContainsString('<h2>Certificates</h2>', $html);
        $this->assertStringContainsString('<header>', $html);
        $this->assertStringContainsString('<h3>C1 Advanced</h3>', $html);
        $this->assertStringContainsString('<h4>Cambridge English</h4>', $html);
        $this->assertStringContainsString('2005-06', $html);
        $this->assertStringContainsString('A', $html);
    }

    public function test_macro_renders_certificate_with_credential_id(): void
    {
        // Arrange
        $items = [CvFixture::certificateByName('Professional Scrum Product Owner')];

        // Act
        $html = $this->twig->render('inline.twig', ['items' => $items]);

        // Assert
        $this->assertStringContainsString('Professional Scrum Product Owner', $html);
        $this->assertStringContainsString('Scrum.org', $html);
        $this->assertStringContainsString('820442', $html);
    }
}
