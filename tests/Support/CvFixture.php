<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Cv\CvDocument;
use App\Cv\CvLoader;

//! @brief Loads the production content/cv/cv.json for parse/route integration checks
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
}
