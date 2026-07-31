<?php

declare(strict_types=1);

namespace App\Cv;

use App\Shared\Support\Result;

//! @brief Flattened, typed CV view model for a single language
//!
//! Parsed from root contact fields plus a `lang-{code}` section. Once constructed,
//! callers can rely on the shape without re-validating raw JSON.
final class CvDocument
{
    //! @param name Full name
    //! @param email Email address
    //! @param phone Phone number
    //! @param website Website URL
    //! @param linkedin LinkedIn URL
    //! @param github GitHub URL
    //! @param language Language code used to select the section
    //! @param summary Professional summary
    //! @param education Education entries
    //! @param certificates Certificate entries
    //! @param experience Discriminated experience entries
    //! @param languages Language proficiency entries
    //! @param skills Skill group map (group key => skill list)
    //! @param skillHighlights Highlight sentences
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $phone,
        public readonly string $website,
        public readonly string $linkedin,
        public readonly string $github,
        public readonly string $language,
        public readonly string $summary,
        public readonly array $education,
        public readonly array $certificates,
        public readonly array $experience,
        public readonly array $languages,
        public readonly array $skills,
        public readonly array $skillHighlights,
    ) {}

    //! @brief Parse raw CV JSON for a language into a typed document
    //! @param raw Decoded JSON object
    //! @param language Language code such as "en"
    //! @return Result<self>
    public static function parse(array $raw, string $language): Result
    {
        $context = 'CV document';
        $sectionKey = 'lang-' . $language;

        if (!isset($raw[$sectionKey]) || !is_array($raw[$sectionKey]) || array_is_list($raw[$sectionKey])) {
            return Result::failure("CV language section \"{$sectionKey}\" is missing");
        }

        /** @var array<string, mixed> $section */
        $section = $raw[$sectionKey];

        $name = CvParsing::requireString($raw, 'name', $context);
        if ($name->isFailure()) {
            return $name;
        }

        $email = CvParsing::requireString($raw, 'email', $context);
        if ($email->isFailure()) {
            return $email;
        }

        $phone = CvParsing::requireString($raw, 'phone', $context);
        if ($phone->isFailure()) {
            return $phone;
        }

        $website = CvParsing::requireString($raw, 'website', $context);
        if ($website->isFailure()) {
            return $website;
        }

        $linkedin = CvParsing::requireString($raw, 'linkedin', $context);
        if ($linkedin->isFailure()) {
            return $linkedin;
        }

        $github = CvParsing::requireString($raw, 'github', $context);
        if ($github->isFailure()) {
            return $github;
        }

        $summary = CvParsing::requireString($section, 'summary', $context);
        if ($summary->isFailure()) {
            return $summary;
        }

        $rawEducation = CvParsing::optionalList($section, 'education', $context);
        if ($rawEducation->isFailure()) {
            return $rawEducation;
        }
        $education = EducationEntry::parseList($rawEducation->getValue());
        if ($education->isFailure()) {
            return $education;
        }

        $rawCertificates = CvParsing::optionalList($section, 'certificates', $context);
        if ($rawCertificates->isFailure()) {
            return $rawCertificates;
        }
        $certificates = CertificateEntry::parseList($rawCertificates->getValue());
        if ($certificates->isFailure()) {
            return $certificates;
        }

        $rawExperience = CvParsing::optionalList($section, 'experience', $context);
        if ($rawExperience->isFailure()) {
            return $rawExperience;
        }
        $experience = ExperienceEntryParser::parseList($rawExperience->getValue());
        if ($experience->isFailure()) {
            return $experience;
        }

        $rawLanguages = CvParsing::optionalList($section, 'languages', $context);
        if ($rawLanguages->isFailure()) {
            return $rawLanguages;
        }
        $languages = LanguageProficiency::parseList($rawLanguages->getValue());
        if ($languages->isFailure()) {
            return $languages;
        }

        $skills = self::parseSkills($section);
        if ($skills->isFailure()) {
            return $skills;
        }

        $rawHighlights = CvParsing::optionalList($section, 'skill_highlights', $context);
        if ($rawHighlights->isFailure()) {
            return $rawHighlights;
        }
        $skillHighlights = CvParsing::stringList(
            $rawHighlights->getValue(),
            'CV document skill_highlights'
        );
        if ($skillHighlights->isFailure()) {
            return $skillHighlights;
        }

        return Result::success(new self(
            name: $name->getValue(),
            email: $email->getValue(),
            phone: $phone->getValue(),
            website: $website->getValue(),
            linkedin: $linkedin->getValue(),
            github: $github->getValue(),
            language: $language,
            summary: $summary->getValue(),
            education: $education->getValue(),
            certificates: $certificates->getValue(),
            experience: $experience->getValue(),
            languages: $languages->getValue(),
            skills: $skills->getValue(),
            skillHighlights: $skillHighlights->getValue(),
        ));
    }

    //! @brief Parse the skills object map
    //! @param section Language section object
    //! @return Result<array<string, list<string>>>
    private static function parseSkills(array $section): Result
    {
        if (!array_key_exists('skills', $section) || $section['skills'] === null) {
            return Result::success([]);
        }

        if (!is_array($section['skills'])) {
            return Result::failure('CV document field "skills" must be an object map');
        }

        // json_decode({}, true) becomes [] — treat empty list as an empty map
        if ($section['skills'] === []) {
            return Result::success([]);
        }

        if (array_is_list($section['skills'])) {
            return Result::failure('CV document field "skills" must be an object map');
        }

        $skills = [];
        foreach ($section['skills'] as $group => $items) {
            if (!is_string($group) || trim($group) === '') {
                return Result::failure('CV document skills group keys must be non-empty strings');
            }
            if (!is_array($items)) {
                return Result::failure("CV document skills.{$group} must be a list of strings");
            }

            $parsed = CvParsing::stringList($items, "CV document skills.{$group}");
            if ($parsed->isFailure()) {
                return $parsed;
            }

            $skills[$group] = $parsed->getValue();
        }

        return Result::success($skills);
    }

    //! @brief Template-facing flattened array representation
    //! @return array<string, mixed>
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'website' => $this->website,
            'linkedin' => $this->linkedin,
            'github' => $this->github,
            'language' => $this->language,
            'summary' => $this->summary,
            'education' => array_map(
                static fn(EducationEntry $entry): array => $entry->toArray(),
                $this->education
            ),
            'certificates' => array_map(
                static fn(CertificateEntry $entry): array => $entry->toArray(),
                $this->certificates
            ),
            'experience' => array_map(
                static fn(ExperienceEntry $entry): array => $entry->toArray(),
                $this->experience
            ),
            'languages' => array_map(
                static fn(LanguageProficiency $entry): array => $entry->toArray(),
                $this->languages
            ),
            'skills' => $this->skills,
            'skill_highlights' => $this->skillHighlights,
            'labels' => CvLabels::forLanguage($this->language),
        ];
    }
}
