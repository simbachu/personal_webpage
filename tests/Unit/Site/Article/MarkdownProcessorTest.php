<?php

declare(strict_types=1);

namespace Tests\Unit\Site\Article;

use App\Site\Article\MarkdownProcessor;
use PHPUnit\Framework\TestCase;

//! @brief Unit tests for MarkdownProcessor (title/meta extraction, footnotes, heading bump)
final class MarkdownProcessorTest extends TestCase
{
    private MarkdownProcessor $processor;

    protected function setUp(): void
    {
        $this->processor = new MarkdownProcessor();
    }

    public function test_process_extracts_title_author_and_date(): void
    {
        //! @section Arrange
        $markdown = <<<'MD'
# Hello World
By: Ada Lovelace
On: 2024-01-15

Body paragraph with **emphasis**.
MD;

        //! @section Act
        $result = $this->processor->process($markdown);

        //! @section Assert
        $this->assertSame('Hello World', $result['title']);
        $this->assertSame('Ada Lovelace', $result['author']);
        $this->assertSame('2024-01-15', $result['date']);
        $this->assertStringContainsString('<strong>emphasis</strong>', $result['content']);
        $this->assertSame([], $result['footnotes']);
    }

    public function test_process_without_metadata_keeps_title_in_content_when_no_heading_metadata_block(): void
    {
        //! @section Arrange
        $markdown = "Just a paragraph.\n";

        //! @section Act
        $result = $this->processor->process($markdown);

        //! @section Assert
        $this->assertNull($result['title']);
        $this->assertNull($result['author']);
        $this->assertNull($result['date']);
        $this->assertStringContainsString('Just a paragraph', $result['content']);
    }

    public function test_process_extracts_manual_footnotes_and_strips_section_from_content(): void
    {
        //! @section Arrange
        $markdown = <<<'MD'
# Footnote Demo
By: Tester
On: 2024-06-01

See the note[^1] for details.

[^1]: First footnote body.
MD;

        //! @section Act
        $result = $this->processor->process($markdown);

        //! @section Assert
        $this->assertSame('Footnote Demo', $result['title']);
        $this->assertNotEmpty($result['footnotes']);
        $this->assertStringContainsString('First footnote body', (string) reset($result['footnotes']));
        $this->assertStringContainsString('footnote-ref', $result['content']);
        $this->assertStringNotContainsString('class="footnotes"', $result['content']);
    }

    public function test_process_bumps_heading_levels_in_body_content(): void
    {
        //! @section Arrange
        $markdown = <<<'MD'
# Title
By: Tester
On: 2024-01-01

Intro paragraph.

## Section
Text.
MD;

        //! @section Act
        $result = $this->processor->process($markdown);

        //! @section Assert
        $this->assertSame('Title', $result['title']);
        $this->assertStringContainsString('<h3>Section</h3>', $result['content']);
    }

    public function test_process_converts_caret_superscript_markup(): void
    {
        //! @section Arrange
        $markdown = <<<'MD'
# Supers

E = mc^2^ in prose.
MD;

        //! @section Act
        $result = $this->processor->process($markdown);

        //! @section Assert
        $this->assertStringContainsString('<sup>2</sup>', $result['content']);
    }
}
