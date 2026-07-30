<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Cv\CertificateEntry;
use App\Cv\CvDocument;
use App\Cv\CvLoader;
use App\Cv\EducationEntry;
use App\Cv\EmployerExperience;
use App\Cv\ExperienceSection;
use App\Cv\LanguageProficiency;
use App\Cv\OrganizationExperience;
use RuntimeException;

//! @brief Loads the real content/cv/cv.json fixture as a typed CvDocument
final class CvFixture
{
    private static ?CvDocument $cached = null;

    //! @brief Absolute path to the production CV JSON fixture
    public static function path(): string
    {
        return TwigTestFactory::projectRoot() . '/content/cv/cv.json';
    }

    //! @brief Typed CV document for a language
    //! @param language Language code such as "en"
    //! @return CvDocument
    public static function document(string $language = 'en'): CvDocument
    {
        if (self::$cached === null || self::$cached->language !== $language) {
            self::$cached = CvLoader::fromString(self::path())->load($language);
        }

        return self::$cached;
    }

    //! @brief Flattened template array for the full CV
    //! @param language Language code such as "en"
    //! @return array<string, mixed>
    public static function cv(string $language = 'en'): array
    {
        return self::document($language)->toArray();
    }

    //! @brief Experience entries as template arrays
    //! @return list<array<string, mixed>>
    public static function experience(): array
    {
        return self::cv()['experience'];
    }

    //! @brief Education entries as template arrays
    //! @return list<array<string, mixed>>
    public static function education(): array
    {
        return self::cv()['education'];
    }

    //! @brief Certificate entries as template arrays
    //! @return list<array<string, mixed>>
    public static function certificates(): array
    {
        return self::cv()['certificates'];
    }

    //! @brief Language entries as template arrays
    //! @return list<array<string, mixed>>
    public static function languages(): array
    {
        return self::cv()['languages'];
    }

    //! @brief Skill groups from the fixture
    //! @return array<string, list<string>>
    public static function skills(): array
    {
        return self::document()->skills;
    }

    //! @brief Skill highlight strings from the fixture
    //! @return list<string>
    public static function skillHighlights(): array
    {
        return self::document()->skillHighlights;
    }

    //! @brief First experience entry matching a company name, as a template array
    //! @param company Company name to find
    //! @return array<string, mixed>
    public static function experienceByCompany(string $company): array
    {
        foreach (self::document()->experience as $entry) {
            if ($entry instanceof EmployerExperience && $entry->company === $company) {
                return $entry->toArray();
            }
        }

        throw new RuntimeException("No experience entry for company: {$company}");
    }

    //! @brief First experience entry matching an organization name, as a template array
    //! @param organization Organization name to find
    //! @return array<string, mixed>
    public static function experienceByOrganization(string $organization): array
    {
        foreach (self::document()->experience as $entry) {
            if ($entry instanceof OrganizationExperience && $entry->organization === $organization) {
                return $entry->toArray();
            }
        }

        throw new RuntimeException("No experience entry for organization: {$organization}");
    }

    //! @brief First experience section blurb, as a template array
    //! @return array<string, mixed>
    public static function experienceSection(): array
    {
        foreach (self::document()->experience as $entry) {
            if ($entry instanceof ExperienceSection) {
                return $entry->toArray();
            }
        }

        throw new RuntimeException('No experience section blurb found in CV fixture');
    }

    //! @brief First education entry matching an institution name, as a template array
    //! @param institution Institution name to find
    //! @return array<string, mixed>
    public static function educationByInstitution(string $institution): array
    {
        foreach (self::document()->education as $entry) {
            /** @var EducationEntry $entry */
            if ($entry->institution === $institution) {
                return $entry->toArray();
            }
        }

        throw new RuntimeException("No education entry for institution: {$institution}");
    }

    //! @brief First certificate entry matching a name, as a template array
    //! @param name Certificate name to find
    //! @return array<string, mixed>
    public static function certificateByName(string $name): array
    {
        foreach (self::document()->certificates as $entry) {
            /** @var CertificateEntry $entry */
            if ($entry->name === $name) {
                return $entry->toArray();
            }
        }

        throw new RuntimeException("No certificate entry for name: {$name}");
    }

    //! @brief First language entry matching a language name, as a template array
    //! @param language Language name to find
    //! @return array<string, mixed>
    public static function languageByName(string $language): array
    {
        foreach (self::document()->languages as $entry) {
            /** @var LanguageProficiency $entry */
            if ($entry->language === $language) {
                return $entry->toArray();
            }
        }

        throw new RuntimeException("No language entry for: {$language}");
    }
}
