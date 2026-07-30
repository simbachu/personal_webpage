<?php

declare(strict_types=1);

namespace App\Cv;

use App\Shared\Support\Result;

//! @brief A single role within an employer or organization experience entry
final class ExperienceRole
{
    //! @param position Job title
    //! @param from Start date (YYYY-MM)
    //! @param to End date (YYYY-MM), or null for Present
    //! @param duration Optional duration label
    //! @param employment Optional employment type
    //! @param summary Optional role summary
    //! @param bullets Achievement bullets
    //! @param skills Skill tags
    public function __construct(
        public readonly string $position,
        public readonly string $from,
        public readonly ?string $to,
        public readonly ?string $duration = null,
        public readonly ?string $employment = null,
        public readonly ?string $summary = null,
        public readonly array $bullets = [],
        public readonly array $skills = [],
    ) {}

    //! @brief Parse an untyped role object into a typed role
    //! @param data Untyped associative array
    //! @return Result<self>
    public static function parse(array $data): Result
    {
        $context = 'Experience role';

        $position = CvParsing::requireString($data, 'position', $context);
        if ($position->isFailure()) {
            return $position;
        }

        $from = CvParsing::requireString($data, 'from', $context);
        if ($from->isFailure()) {
            return $from;
        }

        $to = CvParsing::optionalString($data, 'to', $context);
        if ($to->isFailure()) {
            return $to;
        }

        $duration = CvParsing::optionalString($data, 'duration', $context);
        if ($duration->isFailure()) {
            return $duration;
        }

        $employment = CvParsing::optionalString($data, 'employment', $context);
        if ($employment->isFailure()) {
            return $employment;
        }

        $summary = CvParsing::optionalString($data, 'summary', $context);
        if ($summary->isFailure()) {
            return $summary;
        }

        $rawBullets = CvParsing::optionalList($data, 'bullets', $context);
        if ($rawBullets->isFailure()) {
            return $rawBullets;
        }

        $bullets = CvParsing::stringList($rawBullets->getValue(), "{$context} bullets");
        if ($bullets->isFailure()) {
            return $bullets;
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
            position: $position->getValue(),
            from: $from->getValue(),
            to: $to->getValue(),
            duration: $duration->getValue(),
            employment: $employment->getValue(),
            summary: $summary->getValue(),
            bullets: $bullets->getValue(),
            skills: $skills->getValue(),
        ));
    }

    //! @brief Template-facing array representation
    //! @return array<string, mixed>
    public function toArray(): array
    {
        $data = [
            'position' => $this->position,
            'from' => $this->from,
            'to' => $this->to,
        ];

        if ($this->duration !== null) {
            $data['duration'] = $this->duration;
        }
        if ($this->employment !== null) {
            $data['employment'] = $this->employment;
        }
        if ($this->summary !== null) {
            $data['summary'] = $this->summary;
        }
        if ($this->bullets !== []) {
            $data['bullets'] = $this->bullets;
        }
        if ($this->skills !== []) {
            $data['skills'] = $this->skills;
        }

        return $data;
    }
}
