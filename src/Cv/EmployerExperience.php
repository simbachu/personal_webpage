<?php

declare(strict_types=1);

namespace App\Cv;

use App\Shared\Support\Result;

//! @brief Company employment entry with one or more roles
final class EmployerExperience implements ExperienceEntry
{
    //! @param company Company name
    //! @param location Optional location
    //! @param tenure Optional overall tenure label
    //! @param roles Typed roles at this company
    public function __construct(
        public readonly string $company,
        public readonly ?string $location = null,
        public readonly ?string $tenure = null,
        public readonly array $roles = [],
    ) {}

    //! @brief Parse an untyped company experience object
    //! @param data Untyped associative array
    //! @return Result<self>
    public static function parse(array $data): Result
    {
        $context = 'Employer experience';

        $company = CvParsing::requireString($data, 'company', $context);
        if ($company->isFailure()) {
            return $company;
        }

        $location = CvParsing::optionalString($data, 'location', $context);
        if ($location->isFailure()) {
            return $location;
        }

        $tenure = CvParsing::optionalString($data, 'tenure', $context);
        if ($tenure->isFailure()) {
            return $tenure;
        }

        $roles = self::parseRoles($data, $context);
        if ($roles->isFailure()) {
            return $roles;
        }

        return Result::success(new self(
            company: $company->getValue(),
            location: $location->getValue(),
            tenure: $tenure->getValue(),
            roles: $roles->getValue(),
        ));
    }

    //! @brief Parse the roles list for an employer or organization
    //! @param data Untyped associative array
    //! @param context Human-readable parse context
    //! @return Result<list<ExperienceRole>>
    public static function parseRoles(array $data, string $context): Result
    {
        $rawRoles = CvParsing::optionalList($data, 'roles', $context);
        if ($rawRoles->isFailure()) {
            return $rawRoles;
        }

        $roles = [];
        foreach ($rawRoles->getValue() as $index => $rawRole) {
            if (!is_array($rawRole) || array_is_list($rawRole)) {
                return Result::failure("{$context} roles[{$index}] must be an object");
            }

            $role = ExperienceRole::parse($rawRole);
            if ($role->isFailure()) {
                return Result::failure("{$context} roles[{$index}]: " . $role->getError());
            }

            $roles[] = $role->getValue();
        }

        return Result::success($roles);
    }

    public function toArray(): array
    {
        $data = [
            'company' => $this->company,
            'roles' => array_map(
                static fn(ExperienceRole $role): array => $role->toArray(),
                $this->roles
            ),
        ];

        if ($this->location !== null) {
            $data['location'] = $this->location;
        }
        if ($this->tenure !== null) {
            $data['tenure'] = $this->tenure;
        }

        return $data;
    }
}
