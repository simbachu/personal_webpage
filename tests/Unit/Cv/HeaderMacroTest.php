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

    public function test_macro_renders_name_summary_and_contact_links(): void
    {
        // Arrange
        $cv = CvFixture::cv();

        // Act
        $html = $this->twig->render('inline.twig', ['cv' => $cv]);

        // Assert
        $this->assertStringContainsString('<h1>Jennifer Jonathan Gott</h1>', $html);
        $this->assertStringContainsString('Systems developer with a background in information design', $html);
        $this->assertStringContainsString('<a href="mailto:simbachu@gmail.com">simbachu@gmail.com</a>', $html);
        $this->assertStringContainsString('<a href="tel:+46 704 91 10 97">+46 704 91 10 97</a>', $html);
        $this->assertStringContainsString('<a href="https://www.simbachu.com">https://www.simbachu.com</a>', $html);
        $this->assertStringContainsString('<a href="https://www.linkedin.com/in/jennifer-jonathan-gott-2233aa294/">', $html);
        $this->assertStringContainsString('<a href="https://github.com/simbachu">', $html);
    }
}
