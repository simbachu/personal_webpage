<?php

declare(strict_types=1);

namespace App\Cv;

use App\Shared\Support\FilePath;

//! @brief Loads a plaintext cover letter and splits it into paragraphs
final class CoverLetterLoader
{
    public function __construct(
        private readonly FilePath $filePath
    ) {}

    public static function fromString(string $filePath): self
    {
        return new self(FilePath::fromString($filePath));
    }

    public function exists(): bool
    {
        return $this->filePath->isFile();
    }

    //! @return list<string>
    public function load(): array
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", $this->filePath->readContents());
        $paragraphs = [];

        foreach (explode("\n\n", trim($normalized)) as $block) {
            $paragraph = trim($block);
            if ($paragraph !== '') {
                $paragraphs[] = $paragraph;
            }
        }

        return $paragraphs;
    }
}
