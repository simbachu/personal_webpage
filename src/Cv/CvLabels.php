<?php

declare(strict_types=1);

namespace App\Cv;

//! @brief Localized UI chrome labels for CV section titles and short meta phrases
final class CvLabels
{
    //! @brief Labels for a CV language code
    //! @param language Language code such as "en" or "sv"
    //! @return array{
    //!   experience: string,
    //!   education: string,
    //!   skills: string,
    //!   languages: string,
    //!   certificates: string,
    //!   present: string,
    //!   grade: string,
    //!   skill_groups: array<string, string>
    //! }
    public static function forLanguage(string $language): array
    {
        return match ($language) {
            'sv' => [
                'experience' => 'Erfarenhet',
                'education' => 'Utbildning',
                'skills' => 'Kompetens',
                'languages' => 'Språk',
                'certificates' => 'Certifikat',
                'present' => 'Nuvarande',
                'grade' => 'Betyg',
                'skill_groups' => [
                    'languages' => 'språk',
                    'web_embedded' => 'webb och inbyggda system',
                    'devops_test' => 'devops och test',
                    'leadership' => 'ledarskap',
                    'design' => 'design',
                    'communication' => 'kommunikation',
                    'systems_development' => 'utveckling',
                ],
            ],
            default => [
                'experience' => 'Experience',
                'education' => 'Education',
                'skills' => 'Skills',
                'languages' => 'Languages',
                'certificates' => 'Certificates',
                'present' => 'Present',
                'grade' => 'Grade',
                'skill_groups' => [
                    'languages' => 'languages',
                    'web_embedded' => 'web embedded',
                    'devops_test' => 'devops test',
                    'leadership' => 'leadership',
                    'design' => 'design',
                    'communication' => 'communication',
                    'systems_development' => 'development',
                ],
            ],
        };
    }
}
