<?php

declare(strict_types=1);

namespace App\Service;

use App\Type\Result;
use App\Type\FilePath;
use App\Type\MonsterIdentifier;
use Symfony\Component\Yaml\Yaml;

//! @brief Service for fetching Pokemon opinions from YAML file with caching and error handling
//!
//! This service provides methods to fetch Pokemon opinions from the pokemon_opinions.yaml file
//! with built-in caching and error handling using Result types. It supports both numerical
//! ID and name lookups by normalizing identifiers to lowercase names.
//!
//! @code
//! // Example usage:
//! $service = new PokemonOpinionService();
//! $result = $service->getOpinion('pikachu');
//!
//! if ($result->isSuccess()) {
//!     $opinion = $result->getValue();
//!     echo $opinion['opinion']; // "My sister caught a wild Pikachu..."
//!     echo $opinion['rating']; // "A"
//! } else {
//!     echo "No opinion found for this Pokemon";
//! }
//! @endcode
class PokemonOpinionService
{
    private const OPINIONS_FILE = 'content/pokemon_opinions.yaml';

    private ?array $opinions = null; //!< Cached opinions data

    private string $opinionsFilePath; //!< Path to opinions file

    //! @brief Construct a new PokemonOpinionService instance
    //! @param opinionsFilePath Optional path to opinions file (defaults to standard location)
    public function __construct(?string $opinionsFilePath = null)
    {
        $this->opinionsFilePath = $opinionsFilePath ?? self::OPINIONS_FILE;
    }

    //! @brief Get opinion data for a Pokemon by identifier
    //! @param identifier MonsterIdentifier containing ID or name (e.g., "25" or "pikachu")
    //! @return Result<array{opinion:string,rating:string}> Success containing opinion data, or failure if no opinion found
    public function getOpinion(MonsterIdentifier $identifier): Result
    {
        $name = $this->normalizeToName($identifier);
        if ($name === null) {
            return Result::failure('Unable to normalize identifier to Pokemon name');
        }

        $opinions = $this->loadOpinions();
        if ($opinions === null) {
            return Result::failure('Failed to load opinions data');
        }

        // Try species name first (for Pokemon with multiple forms like Maushold, Deoxys, etc.)
        $speciesName = $this->extractSpeciesName($name);
        $normalizedSpeciesName = mb_strtolower(trim($speciesName));

        if (isset($opinions[$normalizedSpeciesName])) {
            return Result::success($opinions[$normalizedSpeciesName]);
        }

        // Fall back to individual form name
        $normalizedName = mb_strtolower(trim($name));
        if (!isset($opinions[$normalizedName])) {
            return Result::failure('No opinion found for Pokemon: ' . $name);
        }

        return Result::success($opinions[$normalizedName]);
    }

    //! @brief Check if an opinion exists for a Pokemon
    //! @param identifier MonsterIdentifier containing ID or name
    //! @return bool True if opinion exists, false otherwise
    public function hasOpinion(MonsterIdentifier $identifier): bool
    {
        $result = $this->getOpinion($identifier);
        return $result->isSuccess();
    }

    //! @brief Load and cache opinions data from YAML file
    //! @return array<string,array{opinion:string,rating:string}>|null Loaded opinions data or null on failure
    private function loadOpinions(): ?array
    {
        if ($this->opinions !== null) {
            return $this->opinions;
        }

        $filePath = FilePath::fromString($this->opinionsFilePath);
        if (!$filePath->exists()) {
            return null;
        }

        try {
            $content = $filePath->readContents();
            $data = Yaml::parse($content); /** @var array<string,array{opinion:string,rating:string}> $data */

            if (!is_array($data)) {
                return null;
            }

            $this->opinions = $data;
            return $this->opinions;
        } catch (\Throwable $e) {
            return null;
        }
    }

    //! @brief Normalize a MonsterIdentifier to a Pokemon name
    //! @param identifier MonsterIdentifier containing ID or name
    //! @return string|null Normalized Pokemon name in lowercase, or null if unable to normalize
    protected function normalizeToName(MonsterIdentifier $identifier): ?string
    {
        $value = $identifier->getValue();

        // If it's already a name (not numeric), return it
        if (!is_numeric($value)) {
            return $value;
        }

        // For numeric IDs, we need to fetch the name from PokeAPI
        // This is a simplified approach - in a real implementation, you might want
        // to cache this mapping or use a more efficient method
        try {
            $pokeApiService = new PokeApiService();
            $result = $pokeApiService->fetchMonster($identifier);

            if ($result->isFailure()) {
                return null;
            }

            $monsterData = $result->getValue();
            return mb_strtolower($monsterData->name);
        } catch (\Throwable $e) {
            return null;
        }
    }

    //! @brief Extract species name from Pokemon form name
    //! @param name The Pokemon name (e.g., "maushold-family-of-four")
    //! @return string The species name (e.g., "maushold")
    private function extractSpeciesName(string $name): string
    {
        $lowerName = mb_strtolower($name);

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

            // Unown forms (all letters should map to unown)
            // Pattern: unown-a, unown-b, etc.
            // We'll handle this with a pattern match

            // Genesect forms
            'genesect' => 'genesect',
            'genesect-douse' => 'genesect',
            'genesect-shock' => 'genesect',
            'genesect-burn' => 'genesect',
            'genesect-chill' => 'genesect',

            // Alcremie forms (too many to list individually, handle with pattern)

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
        return $name;
    }

    //! @brief Get all available opinion names for debugging/testing purposes
    //! @return array<string> Array of Pokemon names that have opinions
    public function getAllOpinionNames(): array
    {
        $opinions = $this->loadOpinions();
        if ($opinions === null) {
            return [];
        }

        return array_keys($opinions);
    }
}
