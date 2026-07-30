<?php

declare(strict_types=1);

namespace App\Cv;

use App\Shared\Support\Result;

//! @brief A single education entry
final class EducationEntry
{
    //! @param institution School or academy name
    //! @param program Program title
    //! @param from Start date (YYYY-MM)
    //! @param to End date (YYYY-MM)
    //! @param location Optional location
    //! @param tenure Optional tenure note
    //! @param highlights Optional highlight text
    //! @param skills Optional skill tags
    public function __construct(
        public readonly string $institution,
        public readonly string $program,
        public readonly string $from,
        public readonly string $to,
        public readonly ?string $location = null,
        public readonly ?string $tenure = null,
        public readonly ?string $highlights = null,
        public readonly array $skills = [],
    ) {}

    //! @brief Parse an untyped education object
    //! @param data Untyped associative array
    //! @return Result<self>
    public static function parse(array $data): Result
    {
        $context = 'Education entry';

        $institution = CvParsing::requireString($data, 'institution', $context);
        if ($institution->isFailure()) {
            return $institution;
        }

        $program = CvParsing::requireString($data, 'program', $context);
        if ($program->isFailure()) {
            return $program;
        }

        $from = CvParsing::requireString($data, 'from', $context);
        if ($from->isFailure()) {
            return $from;
        }

        $to = CvParsing::requireString($data, 'to', $context);
        if ($to->isFailure()) {
            return $to;
        }

        $location = CvParsing::optionalString($data, 'location', $context);
        if ($location->isFailure()) {
            return $location;
        }

        $tenure = CvParsing::optionalString($data, 'tenure', $context);
        if ($tenure->isFailure()) {
            return $tenure;
        }

        $highlights = CvParsing::optionalString($data, 'highlights', $context);
        if ($highlights->isFailure()) {
            return $highlights;
        }

        $rawSkills = CvParsing::optionalList($data, 'skills', $context);
        if ($rawSkills->isFailure()) {
            return $rawSkills;
        }

        $skills = CvParsing::stringList($rawSkills->getValue(), "{$context} skills");
        if ($skills->isFailure()) {
            return $skills;
        }

        return Result::success(new self(
            institution: $institution->getValue(),
            program: $program->getValue(),
            from: $from->getValue(),
            to: $to->getValue(),
            location: $location->getValue(),
            tenure: $tenure->getValue(),
            highlights: $highlights->getValue(),
            skills: $skills->getValue(),
        ));
    }

    //! @brief Parse a list of education entries
    //! @param items Untyped list
    //! @return Result<list<self>>
    public static function parseList(array $items): Result
    {
        if (!array_is_list($items)) {
            return Result::failure('Education must be a list');
        }

        $entries = [];
        foreach ($items as $index => $item) {
            if (!is_array($item) || array_is_list($item)) {
                return Result::failure("Education[{$index}] must be an object");
            }

            $parsed = self::parse($item);
            if ($parsed->isFailure()) {
                return Result::failure("Education[{$index}]: " . $parsed->getError());
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
            'institution' => $this->institution,
            'program' => $this->program,
            'from' => $this->from,
            'to' => $this->to,
        ];

        if ($this->location !== null) {
            $data['location'] = $this->location;
        }
        if ($this->tenure !== null) {
            $data['tenure'] = $this->tenure;
        }
        if ($this->highlights !== null) {
            $data['highlights'] = $this->highlights;
        }
        if ($this->skills !== []) {
            $data['skills'] = $this->skills;
        }

        return $data;
    }
}
