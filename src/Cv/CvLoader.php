<?php

declare(strict_types=1);

namespace App\Cv;

use App\Shared\Support\FilePath;
use InvalidArgumentException;
use JsonException;
use RuntimeException;

//! @brief Loads CV JSON and parses it into a typed CvDocument
//!
//! Source JSON keeps shared contact fields at the root and language-specific content
//! under keys like `lang-en`. load() parses that into a flattened typed document.
final class CvLoader
{
    //! @brief Construct a loader for a specific CV JSON file
    //! @param filePath Absolute path to cv.json
    public function __construct(
        private readonly FilePath $filePath
    ) {}

    //! @brief Convenience factory from a path string
    //! @param filePath Absolute path to cv.json
    //! @return self
    public static function fromString(string $filePath): self
    {
        return new self(FilePath::fromString($filePath));
    }

    //! @brief Load and parse the CV for a language code
    //! @param language Language code such as "en" or "sv" (looks up `lang-{code}`)
    //! @return CvDocument Typed flattened CV document
    //! @throws RuntimeException If the file cannot be read
    //! @throws JsonException If the JSON is invalid
    //! @throws InvalidArgumentException If parsing fails (missing language or invalid shape)
    public function load(string $language = 'en'): CvDocument
    {
        $raw = $this->decodeFile();
        $parsed = CvDocument::parse($raw, $language);

        if ($parsed->isFailure()) {
            throw new InvalidArgumentException($parsed->getError());
        }

        return $parsed->getValue();
    }

    //! @brief Read and decode the CV JSON file
    //! @return array<string, mixed>
    //! @throws RuntimeException If the file cannot be read
    //! @throws JsonException If the JSON is invalid
    private function decodeFile(): array
    {
        $contents = $this->filePath->readContents();
        $decoded = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

        if (!is_array($decoded) || array_is_list($decoded)) {
            throw new JsonException(
                "CV JSON root must be an object: {$this->filePath->getValue()}"
            );
        }

        return $decoded;
    }
}
