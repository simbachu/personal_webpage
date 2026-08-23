<?php

declare(strict_types=1);

namespace Tests\Unit\Cv;

use App\Cv\CvPrintHtml;
use App\Shared\Support\FilePath;
use PHPUnit\Framework\TestCase;

final class CvPrintHtmlTest extends TestCase
{
    public function test_rewrites_font_stylesheet_to_a_file_uri(): void
    {
        // Arrange
        $stylesheet = FilePath::fromString('D:/fonts/inter.css');
        $html = '<link rel="stylesheet" href="/fonts/inter.css">';

        // Act
        $printed = (new CvPrintHtml($stylesheet))->prepare($html);

        // Assert
        $this->assertStringContainsString(
            'href="file:///D:/fonts/inter.css"',
            $printed
        );
        $this->assertStringNotContainsString('href="/fonts/inter.css"', $printed);
    }
}
