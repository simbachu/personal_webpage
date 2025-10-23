<?php

declare(strict_types=1);

namespace App\Repository;

use App\Type\Result;
use App\Type\RatingData;
use App\Type\MonsterIdentifier;
use App\Type\FilePath;
use Symfony\Component\Yaml\Yaml;

//! @brief File-based implementation of MonsterRatingRepository
//!
//! This implementation reads Pokemon rating data from a YAML file,
//! providing structured access to rating information with caching
//! and species-based lookup capabilities.
//!
//! @code
//! // Example usage:
//! $repository = new FileMonsterRatingRepository('content/pokemon_ratings.yaml');
//! $result = $repository->getRating('maushold');
//!
//! if ($result->isSuccess()) {
//!     $rating = $result->getValue();
//!     echo $rating->speciesName; // "maushold"
//!     echo $rating->rating; // "A"
//! }
//! @endcode
final class FileMonsterRatingRepository implements MonsterRatingRepository
{
    private const DEFAULT_RATINGS_FILE = 'content/pokemon_opinions.yaml';

    private ?array $ratingsCache = null; //!< Cache of loaded ratings keyed by species name

    private string $ratingsFilePath;

    //! @brief Construct a new FileMonsterRatingRepository instance
    //! @param ratingsFilePath Optional path to ratings file (defaults to standard location)
    public function __construct(?string $ratingsFilePath = null)
    {
        $this->ratingsFilePath = $ratingsFilePath ?? self::DEFAULT_RATINGS_FILE;
    }

    //! @brief Get rating for a specific Pokemon species
    //! @param speciesName The species name to look up (e.g., "maushold", "pikachu")
    //! @return Result<RatingData> Success containing RatingData or failure if not found
    public function getRating(string $speciesName): Result
    {
        $ratings = $this->loadRatings();
        if ($ratings === null) {
            return Result::failure('Failed to load ratings data');
        }

        $normalizedName = mb_strtolower(trim($speciesName));
        if (!isset($ratings[$normalizedName])) {
            return Result::failure('No rating found for species: ' . $speciesName);
        }

        return Result::success($ratings[$normalizedName]);
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
        $ratings = $this->loadRatings();
        if ($ratings === null) {
            return [];
        }

        return array_keys($ratings);
    }

    //! @brief Get the total count of ratings
    //! @return int Number of ratings available
    public function getRatingsCount(): int
    {
        $ratings = $this->loadRatings();
        return $ratings === null ? 0 : count($ratings);
    }

    //! @brief Get all ratings as an associative array
    //! @return array<string,RatingData> All ratings keyed by species name
    public function getAllRatings(): array
    {
        $ratings = $this->loadRatings();
        return $ratings ?? [];
    }

    //! @brief Get all ratings for a specific tier
    //! @param tier The tier to filter by (S, A, B, C, D)
    //! @return array<string,RatingData> Ratings for the specified tier, keyed by species name
    public function getRatingsByTier(string $tier): array
    {
        $allRatings = $this->getAllRatings();
        $tierRatings = [];

        $normalizedTier = strtoupper(trim($tier));
        foreach ($allRatings as $speciesName => $rating) {
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
        $allRatings = $this->getAllRatings();
        $tiers = [];

        foreach ($allRatings as $rating) {
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

        // If it's already a species name, return it
        if (!$this->isFormName($value)) {
            return $value;
        }

        return $this->extractSpeciesFromFormName($value);
    }

    //! @brief Load and cache ratings data from YAML file
    //! @return array<string,RatingData>|null Ratings data or null if loading fails
    private function loadRatings(): ?array
    {
        if ($this->ratingsCache !== null) {
            return $this->ratingsCache;
        }

        $filePath = FilePath::fromString($this->ratingsFilePath);
        if (!$filePath->exists()) {
            return null;
        }

        try {
            $content = $filePath->readContents();
            $data = Yaml::parse($content); /** @var array<string, array{opinion: string, rating: string}> $data */

            if (!is_array($data)) {
                return null;
            }

            $ratings = [];
            foreach ($data as $speciesName => $ratingInfo) {
                $normalizedName = mb_strtolower(trim($speciesName));
                $ratings[$normalizedName] = new RatingData(
                    speciesName: $speciesName,
                    opinion: (string)($ratingInfo['opinion'] ?? ''),
                    rating: (string)($ratingInfo['rating'] ?? '')
                );
            }

            $this->ratingsCache = $ratings;
            return $this->ratingsCache;
        } catch (\Throwable $e) {
            return null;
        }
    }

    //! @brief Check if a name represents a Pokemon form (not a base species)
    //! @param name The Pokemon name to check
    //! @return bool True if the name represents a form, false if it's a base species
    private function isFormName(string $name): bool
    {
        $lowerName = mb_strtolower($name);

        // Common form indicators
        $formIndicators = [
            '-family-of-',
            '-normal', '-attack', '-defense', '-speed',
            '-fighting', '-flying', '-poison', '-ground', '-rock',
            '-bug', '-ghost', '-steel', '-fire', '-water', '-grass',
            '-electric', '-psychic', '-ice', '-dragon', '-dark', '-fairy',
            '-douse', '-shock', '-burn', '-chill',
            '-spring', '-summer', '-autumn', '-winter',
        ];

        foreach ($formIndicators as $indicator) {
            if (str_contains($lowerName, $indicator)) {
                return true;
            }
        }

        // Handle Unown forms (unown-a, unown-b, etc.)
        if (str_starts_with($lowerName, 'unown-') && strlen($lowerName) > 6) {
            return true;
        }

        // Handle Alcremie forms
        if (str_starts_with($lowerName, 'alcremie-')) {
            return true;
        }

        return false;
    }

    //! @brief Extract species name from a Pokemon form name
    //! @param formName The form name (e.g., "maushold-family-of-four")
    //! @return string The species name (e.g., "maushold")
    private function extractSpeciesFromFormName(string $formName): string
    {
        $lowerName = mb_strtolower($formName);

        // Handle specific Pokemon with multiple forms that should be rated as species
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
