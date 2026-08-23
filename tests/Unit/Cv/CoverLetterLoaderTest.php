<?php

declare(strict_types=1);

namespace Tests\Unit\Cv;

use App\Cv\CoverLetterLoader;
use App\Shared\Support\FilePath;
use PHPUnit\Framework\TestCase;

final class CoverLetterLoaderTest extends TestCase
{
    /** @var list<string> */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        $this->tempFiles = [];
    }

    public function test_splits_blank_line_separated_paragraphs_and_trims(): void
    {
        // Arrange
        $path = $this->writeLetter("Dear Mikael,\n\n  First paragraph.  \n\nSecond paragraph.\r\n\r\n\r\nKind regards,\n");
        $loader = new CoverLetterLoader($path);

        // Act
        $paragraphs = $loader->load();

        // Assert
        $this->assertSame(
            [
                'Dear Mikael,',
                'First paragraph.',
                'Second paragraph.',
                'Kind regards,',
            ],
            $paragraphs
        );
    }

    public function test_keeps_soft_line_breaks_inside_a_paragraph(): void
    {
        // Arrange
        $path = $this->writeLetter("Kind regards,\nJennifer Jonathan Gott\n");
        $loader = new CoverLetterLoader($path);

        // Act
        $paragraphs = $loader->load();

        // Assert
        $this->assertSame(["Kind regards,\nJennifer Jonathan Gott"], $paragraphs);
    }

    public function test_exists_is_false_when_file_is_missing(): void
    {
        // Arrange
        $loader = CoverLetterLoader::fromString(sys_get_temp_dir() . '/missing_' . uniqid() . '.md');

        // Assert
        $this->assertFalse($loader->exists());
    }

    private function writeLetter(string $contents): FilePath
    {
        $file = sys_get_temp_dir() . '/letter_' . uniqid() . '.md';
        $this->tempFiles[] = $file;
        $path = FilePath::fromString($file);
        $path->writeContents($contents);

        return $path;
    }
}
