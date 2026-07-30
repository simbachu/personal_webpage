<?php

declare(strict_types=1);

namespace Tests\Unit\Cv;

use PHPUnit\Framework\TestCase;
use Tests\Support\CvFixture;
use Tests\Support\TwigTestFactory;
use Twig\Environment;

final class HeaderMacroTest extends TestCase
{
    private Environment $twig;

    protected function setUp(): void
    {
        $this->twig = TwigTestFactory::createMacroEnvironment(<<<'TWIG'
{% import "@cv/header.twig" as header %}
{{ header.header(cv) }}
TWIG);
    }

    public function test_macro_renders_name_and_compact_contact_links(): void
    {
        // Arrange
        $cv = CvFixture::cv();

        // Act
        $html = $this->twig->render('inline.twig', ['cv' => $cv]);

        // Assert
        $this->assertStringContainsString('<h1>Jennifer Jonathan Gott</h1>', $html);
        $this->assertStringNotContainsString('Systems developer with a background in information design', $html);
        $this->assertStringNotContainsString('<dt>Email</dt>', $html);
        $this->assertStringNotContainsString('<dt>Phone</dt>', $html);
        $this->assertStringContainsString('<a href="mailto:simbachu@gmail.com">simbachu@gmail.com</a>', $html);
        $this->assertStringContainsString('<a href="tel:+46-704-911097">+46-704-911097</a>', $html);
        $this->assertStringContainsString('<a href="https://www.simbachu.com">www.simbachu.com</a>', $html);
        $this->assertStringContainsString(
            '<a href="https://www.linkedin.com/in/jennifer-jonathan-gott-2233aa294/">LinkedIn</a>',
            $html
        );
        $this->assertStringContainsString('<a href="https://github.com/simbachu">GitHub</a>', $html);
    }
}
