<?php

declare(strict_types=1);

namespace App\Type;

//! @brief Immutable value object representing Pokemon rating data
//!
//! This class encapsulates rating information for Pokemon species,
//! providing type safety and clear contracts for rating data structure.
//!
//! @code
//! // Example usage:
//! $rating = new RatingData(
//!     speciesName: 'maushold',
//!     opinion: 'They are such a cute little family! So delightful!',
//!     rating: 'A'
//! );
//!
//! echo $rating->speciesName; // "maushold"
//! echo $rating->rating; // "A"
//! echo $rating->opinion; // "They are such a cute little family! So delightful!"
//! @endcode
final class RatingData
{
    //! @brief Construct a new RatingData instance
    //! @param speciesName The species name this rating applies to (e.g., "maushold", "deoxys")
    //! @param opinion The detailed opinion text about this Pokemon species
    //! @param rating The rating tier (S, A, B, C, D)
    public function __construct(
        public readonly string $speciesName,
        public readonly string $opinion,
        public readonly string $rating
    ) {}

    //! @brief Check if this rating has a specific tier
    //! @param tier The tier to check for (S, A, B, C, D)
    //! @return bool True if the rating matches the specified tier
    public function hasTier(string $tier): bool
    {
        return strtoupper($this->rating) === strtoupper($tier);
    }

    //! @brief Check if this rating is in the S tier
    //! @return bool True if this rating is S tier
    public function isSTier(): bool
    {
        return $this->hasTier('S');
    }

    //! @brief Check if this rating is in the A tier
    //! @return bool True if this rating is A tier
    public function isATier(): bool
    {
        return $this->hasTier('A');
    }

    //! @brief Check if this rating is in the B tier
    //! @return bool True if this rating is B tier
    public function isBTier(): bool
    {
        return $this->hasTier('B');
    }

    //! @brief Check if this rating is in the C tier
    //! @return bool True if this rating is C tier
    public function isCTier(): bool
    {
        return $this->hasTier('C');
    }

    //! @brief Check if this rating is in the D tier
    //! @return bool True if this rating is D tier
    public function isDTier(): bool
    {
        return $this->hasTier('D');
    }

    //! @brief Convert this RatingData to an array format suitable for templates
    //! @return array Array representation of the rating data
    public function toArray(): array
    {
        return [
            'species_name' => $this->speciesName,
            'opinion' => $this->opinion,
            'rating' => $this->rating,
        ];
    }

    //! @brief Create a RatingData instance from array data
    //! @param data Array containing rating data with keys 'species_name', 'opinion', 'rating'
    //! @return self New RatingData instance
    public static function fromArray(array $data): self
    {
        return new self(
            speciesName: (string)($data['species_name'] ?? $data['speciesName'] ?? ''),
            opinion: (string)($data['opinion'] ?? ''),
            rating: (string)($data['rating'] ?? '')
        );
    }
}
