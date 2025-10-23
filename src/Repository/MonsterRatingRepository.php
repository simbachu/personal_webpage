<?php

declare(strict_types=1);

namespace App\Repository;

use App\Type\Result;
use App\Type\RatingData;
use App\Type\MonsterIdentifier;

//! @brief Repository interface for Pokemon rating data access
//!
//! This interface defines the contract for accessing Pokemon rating information,
//! providing a clean separation between business logic and data access concerns.
//! Implementations can vary from file-based to database-based storage.
//!
//! @code
//! // Example usage:
//! $repository = new FileMonsterRatingRepository('path/to/ratings.yaml');
//! $result = $repository->getRating('maushold');
//!
//! if ($result->isSuccess()) {
//!     $rating = $result->getValue();
//!     echo $rating->speciesName; // "maushold"
//!     echo $rating->rating; // "A"
//! }
//! @endcode
interface MonsterRatingRepository
{
    //! @brief Get rating data for a Pokemon species
    //! @param speciesName The species name to look up (e.g., "maushold", "deoxys")
    //! @return Result<RatingData> Success containing rating data, or failure if not found
    public function getRating(string $speciesName): Result;

    //! @brief Check if a rating exists for a Pokemon species
    //! @param speciesName The species name to check
    //! @return bool True if rating exists, false otherwise
    public function hasRating(string $speciesName): bool;

    //! @brief Get all species names that have ratings
    //! @return array<string> Array of species names with ratings
    public function getAllSpeciesNames(): array;

    //! @brief Get the total number of rated species
    //! @return int Number of species with ratings
    public function getRatingsCount(): int;

    //! @brief Get all rating data as an associative array
    //! @return array<string, RatingData> Map of species name to rating data
    public function getAllRatings(): array;

    //! @brief Get ratings for a specific tier
    //! @param tier The rating tier (S, A, B, C, D)
    //! @return array<string, RatingData> Map of species name to rating data for the tier
    public function getRatingsByTier(string $tier): array;

    //! @brief Get all available rating tiers in the repository
    //! @return array<string> Array of unique rating tiers (e.g., ['S', 'A', 'B'])
    public function getAllTiers(): array;

    //! @brief Extract species name from a Pokemon identifier for rating lookup
    //! @param identifier MonsterIdentifier to extract species name from
    //! @return string The species name for rating purposes
    public function extractSpeciesName(MonsterIdentifier $identifier): string;
}
