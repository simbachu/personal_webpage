<?php

declare(strict_types=1);

namespace Tests\Unit\Cv;

use App\Cv\CvLanguageSelector;
use PHPUnit\Framework\TestCase;

final class CvLanguageSelectorTest extends TestCase
{
    public function test_query_language_takes_precedence_over_accept_language(): void
    {
        // Arrange
        $selector = new CvLanguageSelector();

        // Act
        $language = $selector->select('en', 'sv-SE,sv;q=0.9');

        // Assert
        $this->assertSame('en', $language);
    }

    public function test_selects_supported_regional_accept_language(): void
    {
        // Arrange
        $selector = new CvLanguageSelector();

        // Act
        $language = $selector->select(null, 'sv-SE,sv;q=0.9,en;q=0.8');

        // Assert
        $this->assertSame('sv', $language);
    }

    public function test_selects_supported_language_with_highest_quality(): void
    {
        // Arrange
        $selector = new CvLanguageSelector();

        // Act
        $language = $selector->select(null, 'sv;q=0.4,en-GB;q=0.9');

        // Assert
        $this->assertSame('en', $language);
    }

    public function test_ignores_unsupported_query_language_and_uses_accept_language(): void
    {
        // Arrange
        $selector = new CvLanguageSelector();

        // Act
        $language = $selector->select('de', 'sv;q=0.8');

        // Assert
        $this->assertSame('sv', $language);
    }

    public function test_falls_back_to_english_when_no_supported_language_is_requested(): void
    {
        // Arrange
        $selector = new CvLanguageSelector();

        // Act
        $language = $selector->select(null, 'de-DE,fr;q=0.9');

        // Assert
        $this->assertSame('en', $language);
    }

    public function test_ignores_languages_with_zero_quality(): void
    {
        // Arrange
        $selector = new CvLanguageSelector();

        // Act
        $language = $selector->select(null, 'sv;q=0,en;q=0.5');

        // Assert
        $this->assertSame('en', $language);
    }
}
