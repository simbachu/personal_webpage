<?php

declare(strict_types=1);

namespace App\Cv;

use App\Shared\Support\Result;

//! @brief Condensed experience section blurb (no roles)
final class ExperienceSection implements ExperienceEntry
{
    //! @param section Section heading
    //! @param summary Optional summary text
    public function __construct(
        public readonly string $section,
        public readonly ?string $summary = null,
    ) {}

    //! @brief Parse an untyped section blurb object
    //! @param data Untyped associative array
    //! @return Result<self>
    public static function parse(array $data): Result
    {
        $context = 'Experience section';

        $section = CvParsing::requireString($data, 'section', $context);
        if ($section->isFailure()) {
            return $section;
        }

        $summary = CvParsing::optionalString($data, 'summary', $context);
        if ($summary->isFailure()) {
            return $summary;
        }

        return Result::success(new self(
            section: $section->getValue(),
            summary: $summary->getValue(),
        ));
    }

    public function toArray(): array
    {
        $data = [
            'section' => $this->section,
        ];

        if ($this->summary !== null) {
            $data['summary'] = $this->summary;
        }

        return $data;
    }
}
