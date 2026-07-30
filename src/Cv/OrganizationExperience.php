<?php

declare(strict_types=1);

namespace App\Cv;

use App\Shared\Support\Result;

//! @brief Organization experience entry (e.g. military service) with roles
final class OrganizationExperience implements ExperienceEntry
{
    //! @param organization Organization name
    //! @param location Optional location
    //! @param roles Typed roles at this organization
    public function __construct(
        public readonly string $organization,
        public readonly ?string $location = null,
        public readonly array $roles = [],
    ) {}

    //! @brief Parse an untyped organization experience object
    //! @param data Untyped associative array
    //! @return Result<self>
    public static function parse(array $data): Result
    {
        $context = 'Organization experience';

        $organization = CvParsing::requireString($data, 'organization', $context);
        if ($organization->isFailure()) {
            return $organization;
        }

        $location = CvParsing::optionalString($data, 'location', $context);
        if ($location->isFailure()) {
            return $location;
        }

        $roles = EmployerExperience::parseRoles($data, $context);
        if ($roles->isFailure()) {
            return $roles;
        }

        return Result::success(new self(
            organization: $organization->getValue(),
            location: $location->getValue(),
            roles: $roles->getValue(),
        ));
    }

    public function toArray(): array
    {
        $data = [
            'organization' => $this->organization,
            'roles' => array_map(
                static fn(ExperienceRole $role): array => $role->toArray(),
                $this->roles
            ),
        ];

        if ($this->location !== null) {
            $data['location'] = $this->location;
        }

        return $data;
    }
}
