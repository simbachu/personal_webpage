<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\MonsterRatingRepository;
use App\Repository\FileMonsterRatingRepository;
use App\Type\Result;
use App\Type\RatingData;
use App\Type\MonsterIdentifier;

//! @brief Service for managing Pokemon rating data using repository pattern
//!
//! This service provides the same interface as PokemonOpinionService but uses
//! the repository pattern for better separation of concerns, testability,
//! and extensibility. It delegates to a MonsterRatingRepository implementation
//! while maintaining backward compatibility.
//!
//! @code
//! // Example usage:
//! $repository = new FileMonsterRatingRepository();
//! $service = new MonsterRatingService($repository);
//! $result = $service->getRating('maushold');
//!
//! if ($result->isSuccess()) {
//!     $rating = $result->getValue();
//!     echo $rating->speciesName; // "maushold"
//!     echo $rating->rating; // "A"
//! }
//! @endcode
final class MonsterRatingService
{
    private MonsterRatingRepository $repository;

    //! @brief Construct a new MonsterRatingService instance
    //! @param repository The repository implementation to use (defaults to file-based)
    public function __construct(?MonsterRatingRepository $repository = null)
    {
        $this->repository = $repository ?? new FileMonsterRatingRepository();
    }

    //! @brief Get rating data for a Pokemon by identifier
    //! @param identifier MonsterIdentifier containing ID or name (e.g., "25" or "pikachu")
    //! @return Result<RatingData> Success containing rating data, or failure if no rating found
    public function getRating(MonsterIdentifier $identifier): Result
    {
        $speciesName = $this->repository->extractSpeciesName($identifier);
        return $this->repository->getRating($speciesName);
    }

    //! @brief Check if a rating exists for a Pokemon
    //! @param identifier MonsterIdentifier containing ID or name
    //! @return bool True if rating exists, false otherwise
    public function hasRating(MonsterIdentifier $identifier): bool
    {
        $speciesName = $this->repository->extractSpeciesName($identifier);
        return $this->repository->hasRating($speciesName);
    }

    //! @brief Get all available rating names for debugging/testing purposes
    //! @return array<string> Array of Pokemon species names that have ratings
    public function getAllRatingNames(): array
    {
        return $this->repository->getAllSpeciesNames();
    }

    //! @brief Get the total number of rated Pokemon species
    //! @return int Number of species with ratings
    public function getRatingsCount(): int
    {
        return $this->repository->getRatingsCount();
    }

    //! @brief Get all rating data as structured RatingData objects
    //! @return array<string, RatingData> Map of species name to rating data
    public function getAllRatings(): array
    {
        return $this->repository->getAllRatings();
    }

    //! @brief Get ratings for a specific tier
    //! @param tier The rating tier (S, A, B, C, D)
    //! @return array<string, RatingData> Map of species name to rating data for the tier
    public function getRatingsByTier(string $tier): array
    {
        return $this->repository->getRatingsByTier($tier);
    }

    //! @brief Get all available rating tiers
    //! @return array<string> Array of unique rating tiers
    public function getAllTiers(): array
    {
        return $this->repository->getAllTiers();
    }

    //! @brief Get the underlying repository (for advanced usage or testing)
    //! @return MonsterRatingRepository The underlying repository instance
    public function getRepository(): MonsterRatingRepository
    {
        return $this->repository;
    }
}
