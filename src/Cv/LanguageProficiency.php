<?php

declare(strict_types=1);

namespace App\Cv;

use App\Shared\Support\Result;

//! @brief A spoken/written language proficiency entry
final class LanguageProficiency
{
    //! @param language Language name
    //! @param level Proficiency level
    //! @param certificate Optional certificate note
    public function __construct(
        public readonly string $language,
        public readonly string $level,
        public readonly ?string $certificate = null,
    ) {}

    //! @brief Parse an untyped language object
    //! @param data Untyped associative array
    //! @return Result<self>
    public static function parse(array $data): Result
    {
        $context = 'Language entry';

        $language = CvParsing::requireString($data, 'language', $context);
        if ($language->isFailure()) {
            return $language;
        }

        $level = CvParsing::requireString($data, 'level', $context);
        if ($level->isFailure()) {
            return $level;
        }

        $certificate = CvParsing::optionalString($data, 'certificate', $context);
        if ($certificate->isFailure()) {
            return $certificate;
        }

        return Result::success(new self(
            language: $language->getValue(),
            level: $level->getValue(),
            certificate: $certificate->getValue(),
        ));
    }

    //! @brief Parse a list of language entries
    //! @param items Untyped list
    //! @return Result<list<self>>
    public static function parseList(array $items): Result
    {
        if (!array_is_list($items)) {
            return Result::failure('Languages must be a list');
        }

        $entries = [];
        foreach ($items as $index => $item) {
            if (!is_array($item) || array_is_list($item)) {
                return Result::failure("Languages[{$index}] must be an object");
            }

            $parsed = self::parse($item);
            if ($parsed->isFailure()) {
                return Result::failure("Languages[{$index}]: " . $parsed->getError());
            }

            $entries[] = $parsed->getValue();
        }

        return Result::success($entries);
    }

    //! @brief Template-facing array representation
    //! @return array<string, mixed>
    public function toArray(): array
    {
        $data = [
            'language' => $this->language,
            'level' => $this->level,
        ];

        if ($this->certificate !== null) {
            $data['certificate'] = $this->certificate;
        }

        return $data;
    }
}
