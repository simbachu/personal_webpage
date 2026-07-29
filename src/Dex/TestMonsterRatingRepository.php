<?php

declare(strict_types=1);

namespace App\Repository;

use App\Type\Result;
use App\Type\RatingData;
use App\Type\MonsterIdentifier;

//! @brief Test implementation of MonsterRatingRepository for unit testing
//!
//! This implementation provides an in-memory rating repository that can be
//! easily configured with test data, making it ideal for unit testing scenarios
//! where you need predictable and controllable rating data.
//!
//! @code
//! // Example usage in tests:
//! $repository = new TestMonsterRatingRepository();
//! $repository->addRating('maushold', 'A', 'They are such a cute little family!');
//! $repository->addRating('pikachu', 'A', 'Most iconic Pokemon ever!');
//!
//! $result = $repository->getRating('maushold');
//! $this->assertTrue($result->isSuccess());
//! @endcode
final class TestMonsterRatingRepository implements MonsterRatingRepository
{
    private array $ratings = []; //!< In-memory storage of ratings keyed by normalized species name
    private array $formToSpeciesMapping = []; //!< Mapping of form names to species names for testing

    //! @brief Add a rating to the test repository
    //! @param speciesName The species name to add rating for
    //! @param rating The rating tier (S, A, B, C, D)
    //! @param opinion The opinion text for this species
    public function addRating(string $speciesName, string $rating, string $opinion): void
    {
        $normalizedName = mb_strtolower(trim($speciesName));
        $this->ratings[$normalizedName] = new RatingData(
            speciesName: $speciesName,
            opinion: $opinion,
            rating: $rating
        );
    }

    //! @brief Add a form-to-species mapping for testing species extraction
    //! @param formName The form name (e.g., "maushold-family-of-four")
    //! @param speciesName The corresponding species name (e.g., "maushold")
    public function addFormMapping(string $formName, string $speciesName): void
    {
        $normalizedForm = mb_strtolower(trim($formName));
        $normalizedSpecies = mb_strtolower(trim($speciesName));
        $this->formToSpeciesMapping[$normalizedForm] = $normalizedSpecies;
    }

    //! @brief Get rating for a specific Pokemon species
    //! @param speciesName The species name to look up
    //! @return Result<RatingData> Success containing RatingData or failure if not found
    public function getRating(string $speciesName): Result
    {
        $normalizedName = mb_strtolower(trim($speciesName));
        if (!isset($this->ratings[$normalizedName])) {
            return Result::failure('No rating found for species: ' . $speciesName);
        }

        return Result::success($this->ratings[$normalizedName]);
    }

    //! @brief Check if a species has a rating
    //! @param speciesName The species name to check
    //! @return bool True if the species has a rating, false otherwise
    public function hasRating(string $speciesName): bool
    {
        $result = $this->getRating($speciesName);
        return $result->isSuccess();
    }

    //! @brief Get all species names that have ratings
    //! @return array List of all species names with ratings
    public function getAllSpeciesNames(): array
    {
        return array_keys($this->ratings);
    }

    //! @brief Get the total count of ratings
    //! @return int Number of ratings available
    public function getRatingsCount(): int
    {
        return count($this->ratings);
    }

    //! @brief Get all ratings as an associative array
    //! @return array<string,RatingData> All ratings keyed by species name
    public function getAllRatings(): array
    {
        return $this->ratings;
    }

    //! @brief Get all ratings for a specific tier
    //! @param tier The tier to filter by (S, A, B, C, D)
    //! @return array<string,RatingData> Ratings for the specified tier, keyed by species name
    public function getRatingsByTier(string $tier): array
    {
        $tierRatings = [];
        $normalizedTier = strtoupper(trim($tier));

        foreach ($this->ratings as $speciesName => $rating) {
            if (strtoupper($rating->rating) === $normalizedTier) {
                $tierRatings[$speciesName] = $rating;
            }
        }

        return $tierRatings;
    }

    //! @brief Get all unique tiers that have ratings
    //! @return array List of all tiers (S, A, B, C, D) sorted alphabetically
    public function getAllTiers(): array
    {
        $tiers = [];

        foreach ($this->ratings as $rating) {
            $tier = strtoupper(trim($rating->rating));
            if (!in_array($tier, $tiers, true)) {
                $tiers[] = $tier;
            }
        }

        sort($tiers);
        return $tiers;
    }

    //! @brief Extract species name from a MonsterIdentifier, handling form variants
    //! @param identifier MonsterIdentifier containing either species name or form name
    //! @return string The species name (e.g., "maushold" from "maushold-family-of-four")
    public function extractSpeciesName(MonsterIdentifier $identifier): string
    {
        $value = $identifier->getValue();
        $normalizedValue = mb_strtolower(trim($value));

        // Check if we have a specific mapping for this form
        if (isset($this->formToSpeciesMapping[$normalizedValue])) {
            return $this->formToSpeciesMapping[$normalizedValue];
        }

        // Use default species extraction logic
        return $this->extractSpeciesFromFormName($value);
    }

    //! @brief Clear all ratings and mappings (useful for test cleanup)
    public function clear(): void
    {
        $this->ratings = [];
        $this->formToSpeciesMapping = [];
    }

    //! @brief Extract species name from a Pokemon form name (default logic)
    //! @param formName The form name (e.g., "maushold-family-of-four")
    //! @return string The species name (e.g., "maushold")
    private function extractSpeciesFromFormName(string $formName): string
    {
        $lowerName = mb_strtolower($formName);

        // Handle specific Pokemon with multiple forms
        $speciesMapping = [
            // Maushold forms
            'maushold-family-of-four' => 'maushold',
            'maushold-family-of-three' => 'maushold',

            // Deoxys forms
            'deoxys-normal' => 'deoxys',
            'deoxys-attack' => 'deoxys',
            'deoxys-defense' => 'deoxys',
            'deoxys-speed' => 'deoxys',

            // Arceus forms
            'arceus-normal' => 'arceus',
            'arceus-fighting' => 'arceus',
            'arceus-flying' => 'arceus',
            'arceus-poison' => 'arceus',
            'arceus-ground' => 'arceus',
            'arceus-rock' => 'arceus',
            'arceus-bug' => 'arceus',
            'arceus-ghost' => 'arceus',
            'arceus-steel' => 'arceus',
            'arceus-fire' => 'arceus',
            'arceus-water' => 'arceus',
            'arceus-grass' => 'arceus',
            'arceus-electric' => 'arceus',
            'arceus-psychic' => 'arceus',
            'arceus-ice' => 'arceus',
            'arceus-dragon' => 'arceus',
            'arceus-dark' => 'arceus',
            'arceus-fairy' => 'arceus',

            // Genesect forms
            'genesect' => 'genesect',
            'genesect-douse' => 'genesect',
            'genesect-shock' => 'genesect',
            'genesect-burn' => 'genesect',
            'genesect-chill' => 'genesect',

            // Deerling/Sawsbuck seasonal forms
            'deerling-spring' => 'deerling',
            'deerling-summer' => 'deerling',
            'deerling-autumn' => 'deerling',
            'deerling-winter' => 'deerling',
            'sawsbuck-spring' => 'sawsbuck',
            'sawsbuck-summer' => 'sawsbuck',
            'sawsbuck-autumn' => 'sawsbuck',
            'sawsbuck-winter' => 'sawsbuck',
        ];

        // Check exact matches first
        if (isset($speciesMapping[$lowerName])) {
            return $speciesMapping[$lowerName];
        }

        // Handle Unown forms (any unown-* should map to unown)
        if (str_starts_with($lowerName, 'unown-')) {
            return 'unown';
        }

        // Handle Alcremie forms (any alcremie-* should map to alcremie)
        if (str_starts_with($lowerName, 'alcremie-')) {
            return 'alcremie';
        }

        // For other Pokemon, return the name as-is (they are their own species)
        return $formName;
    }
}
