<?php

declare(strict_types=1);

namespace App\Cv;

use App\Shared\Support\Result;

//! @brief Parses untyped experience list items into a discriminated ExperienceEntry
final class ExperienceEntryParser
{
    //! @brief Parse one experience entry by discriminating on company/organization/section
    //! @param data Untyped associative array
    //! @return Result<ExperienceEntry>
    public static function parse(array $data): Result
    {
        if (array_key_exists('section', $data)) {
            return ExperienceSection::parse($data);
        }

        if (array_key_exists('organization', $data)) {
            return OrganizationExperience::parse($data);
        }

        if (array_key_exists('company', $data)) {
            return EmployerExperience::parse($data);
        }

        return Result::failure(
            'Experience entry must have one of: company, organization, or section'
        );
    }

    //! @brief Parse a list of experience entries
    //! @param items Untyped list
    //! @return Result<list<ExperienceEntry>>
    public static function parseList(array $items): Result
    {
        if (!array_is_list($items)) {
            return Result::failure('Experience must be a list');
        }

        $entries = [];
        foreach ($items as $index => $item) {
            if (!is_array($item) || array_is_list($item)) {
                return Result::failure("Experience[{$index}] must be an object");
            }

            $parsed = self::parse($item);
            if ($parsed->isFailure()) {
                return Result::failure("Experience[{$index}]: " . $parsed->getError());
            }

            $entries[] = $parsed->getValue();
        }

        return Result::success($entries);
    }
}
