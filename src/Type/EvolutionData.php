<?php

declare(strict_types=1);

namespace App\Type;

//! @brief Immutable value object representing Pokemon evolution data
//!
//! Represents either a precursor (what this Pokemon evolves from) or
//! a successor (what this Pokemon evolves into). Contains the evolution's
//! name and URL for navigation.
//!
//! @code
//! // Example usage:
//! $evolution = new EvolutionData('Raichu', '/dex/raichu');
//! echo $evolution->name; // "Raichu"
//! echo $evolution->url; // "/dex/raichu"
//! @endcode
final class EvolutionData
{
    //! @brief Construct a new EvolutionData instance
    //! @param name The evolution's name in title case (e.g., "Raichu")
    //! @param url The URL path to the evolution's dex page (e.g., "/dex/raichu")
    public function __construct(
        public readonly string $name,
        public readonly string $url
    ) {}

    //! @brief Convert this EvolutionData to an array format suitable for template rendering
    //! @return array{name: string, url: string} Array representation with evolution name and URL
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'url' => $this->url,
        ];
    }

    //! @brief Create an EvolutionData instance from array data
    //! @param data Array data containing evolution name and URL
    //! @return self New EvolutionData instance created from the array data
    public static function fromArray(array $data): self
    {
        return new self(
            name: (string)($data['name'] ?? ''),
            url: (string)($data['url'] ?? '')
        );
    }
}
