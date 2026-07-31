<?php

declare(strict_types=1);

namespace App\Cv;

//! @brief Selects a supported CV language from request preferences
final class CvLanguageSelector
{
    private const DEFAULT_LANGUAGE = 'en';
    private const SUPPORTED_LANGUAGES = ['en', 'sv'];

    //! @brief Select a language, preferring an explicit query override
    //! @param queryLanguage Value of the lang query parameter
    //! @param acceptLanguage Value of the Accept-Language header
    public function select(?string $queryLanguage, ?string $acceptLanguage): string
    {
        $queryMatch = $this->supportedPrimaryLanguage($queryLanguage);
        if ($queryMatch !== null) {
            return $queryMatch;
        }

        $bestLanguage = null;
        $bestQuality = 0.0;

        foreach (explode(',', $acceptLanguage ?? '') as $preference) {
            $parts = array_map('trim', explode(';', $preference));
            $language = $this->supportedPrimaryLanguage($parts[0] ?? null);
            if ($language === null) {
                continue;
            }

            $quality = $this->quality($parts);
            if ($quality > $bestQuality) {
                $bestLanguage = $language;
                $bestQuality = $quality;
            }
        }

        return $bestLanguage ?? self::DEFAULT_LANGUAGE;
    }

    //! @param parts Accept-Language preference and parameters
    private function quality(array $parts): float
    {
        foreach (array_slice($parts, 1) as $parameter) {
            if (preg_match('/^q\s*=\s*(0(?:\.\d+)?|1(?:\.0+)?)$/i', $parameter, $matches) === 1) {
                return (float)$matches[1];
            }
        }

        return 1.0;
    }

    private function supportedPrimaryLanguage(?string $language): ?string
    {
        if ($language === null) {
            return null;
        }

        $primaryLanguage = strtolower(explode('-', trim($language), 2)[0]);

        return in_array($primaryLanguage, self::SUPPORTED_LANGUAGES, true)
            ? $primaryLanguage
            : null;
    }
}
