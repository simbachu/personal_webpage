<?php

declare(strict_types=1);

namespace Tests\Unit\Cv;

use App\Cv\CoverLetterLoader;
use App\Cv\CoverLetterRouteHandler;
use App\Cv\CvLoader;
use App\Shared\Http\HttpStatusCode;
use App\Shared\Http\Route;
use App\Shared\Http\TemplateName;
use App\Shared\Support\FilePath;
use PHPUnit\Framework\TestCase;
use Tests\Support\CvFixture;

final class CoverLetterRouteHandlerTest extends TestCase
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

    public function test_returns_cv_header_data_and_letter_paragraphs(): void
    {
        // Arrange
        $letterPath = $this->writeLetter("Dear Mikael,\n\nI am applying.\n\nKind regards,\n");
        $handler = new CoverLetterRouteHandler(
            CvLoader::fromString(CvFixture::path()),
            new CoverLetterLoader($letterPath)
        );
        $route = new Route('/cover-letter', TemplateName::COVER_LETTER);

        // Act
        $result = $handler->handle($route);

        // Assert
        $this->assertSame(TemplateName::COVER_LETTER, $result->getTemplate());
        $this->assertSame(HttpStatusCode::OK, $result->getStatusCode());
        $data = $result->getData();
        $this->assertSame(CvFixture::cv('en'), $data['cv']);
        $this->assertSame(
            ['Dear Mikael,', 'I am applying.', 'Kind regards,'],
            $data['paragraphs']
        );
        $this->assertSame($data['cv']['name'] . ' — Cover letter', $data['meta']['title']);
    }

    public function test_returns_not_found_when_letter_file_is_missing(): void
    {
        // Arrange
        $handler = new CoverLetterRouteHandler(
            CvLoader::fromString(CvFixture::path()),
            CoverLetterLoader::fromString(sys_get_temp_dir() . '/missing_' . uniqid() . '.md')
        );
        $route = new Route('/cover-letter', TemplateName::COVER_LETTER);

        // Act
        $result = $handler->handle($route);

        // Assert
        $this->assertSame(TemplateName::NOT_FOUND, $result->getTemplate());
        $this->assertSame(HttpStatusCode::NOT_FOUND, $result->getStatusCode());
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
