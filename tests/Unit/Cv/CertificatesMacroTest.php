<?php

declare(strict_types=1);

namespace Tests\Unit\Cv;

use PHPUnit\Framework\TestCase;
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
        $items = [[
            'name' => 'C1 Advanced',
            'issuer' => 'Cambridge English',
            'issued' => '2005-06',
            'grade' => 'A',
        ]];

        // Act
        $html = $this->twig->render('inline.twig', ['items' => $items]);

        // Assert
        $this->assertStringContainsString('<h2>Certificates</h2>', $html);
        $this->assertStringContainsString('<h3>C1 Advanced</h3>', $html);
        $this->assertStringContainsString('<h4>Cambridge English</h4>', $html);
        $this->assertStringContainsString('<span class="meta">Grade: A</span>', $html);
        $this->assertStringContainsString('2005-06', $html);
    }

    public function test_macro_renders_credential_id_inline(): void
    {
        // Arrange
        $items = [[
            'name' => 'Example Cert',
            'issuer' => 'Example Org',
            'issued' => '2022-07',
            'credential_id' => '820442',
        ]];

        // Act
        $html = $this->twig->render('inline.twig', ['items' => $items]);

        // Assert
        $this->assertStringContainsString('<span class="meta">ID: 820442</span>', $html);
        $this->assertStringNotContainsString('Grade:', $html);
    }
}
