<?php

declare(strict_types=1);

namespace App\Presenter;

use App\Service\PokeApiService;
use App\Service\PokemonOpinionService;
use App\Service\MonsterRatingServiceAdapter;
use App\Type\MonsterData;
use App\Type\MonsterIdentifier;
use App\Type\TemplateName;

//! @brief Presenter for Pokemon dex detail view with clean separation of concerns
//!
//! This presenter handles the presentation logic for Pokemon dex pages, providing
//! clean MonsterData objects to templates while internally managing Result types
//! from the service layer. The Result type boundary is maintained here.
//!
//! @code
//! // Example usage:
//! $presenter = new DexPresenter($pokeApiService, 300);
//!
//! try {
//!     $monsterData = $presenter->fetchMonsterData('pikachu');
//!     $viewData = $presenter->present($monsterData);
//!     // Use $viewData in template
//! } catch (\RuntimeException $e) {
//!     // Handle service failure
//! }
//! @endcode
class DexPresenter
{
    private PokeApiService $pokeapi; //!< Service for fetching pokemon data
    private PokemonOpinionService|MonsterRatingServiceAdapter $opinionService; //!< Service for fetching pokemon opinions
    private int $cacheTtl; //!< Cache TTL in seconds

    //! @brief Construct a new DexPresenter instance
    //! @param pokeapi PokeAPI service for fetching Pokemon data
    //! @param opinionService Pokemon opinion service or compatible adapter for fetching opinions
    //! @param cacheTtl Cache time-to-live in seconds for Pokemon data (defaults to 300)
    public function __construct(PokeApiService $pokeapi, PokemonOpinionService|MonsterRatingServiceAdapter $opinionService, int $cacheTtl = 300)
    {
        $this->pokeapi = $pokeapi;
        $this->opinionService = $opinionService;
        $this->cacheTtl = $cacheTtl;
    }

    //! @brief Prepare view model for the dex page from clean MonsterData
    //! @param monsterData The MonsterData object to present to the template
    //! @return array{template:TemplateName,monster:array} View data structure for template rendering
    public function present(MonsterData $monsterData): array
    {
        $monsterArray = $monsterData->toArray();

        // Try to add opinion data if available
        // Use species name if available, otherwise fall back to individual form name
        $lookupName = $monsterData->speciesName ?? $monsterData->name;
        $opinionIdentifier = MonsterIdentifier::fromString($lookupName);

        $opinionResult = $this->opinionService->getOpinion($opinionIdentifier);

        if ($opinionResult->isSuccess()) {
            $opinion = $opinionResult->getValue();
            $monsterArray['opinion'] = $opinion['opinion'];
            $monsterArray['rating'] = $opinion['rating'];
        }

        return [
            'template' => TemplateName::DEX,
            'monster' => $monsterArray,
        ];
    }

    //! @brief Fetch monster data from service and handle Result type internally
    //! @param identifier MonsterIdentifier containing ID or name (e.g., "25" or "pikachu")
    //! @return MonsterData Clean MonsterData object for presentation
    //! @throws \RuntimeException If the service returns a failure Result
    public function fetchMonsterData(MonsterIdentifier $identifier): MonsterData
    {
        $result = $this->pokeapi->fetchMonster($identifier, null, $this->cacheTtl);

        if ($result->isFailure()) {
            throw new \RuntimeException($result->getError());
        }

        return $result->getValue();
    }

    //! @brief Build a tier list grouped by rating with sprite image and URL
    //! @return array{name:string,tiers:array<int,array{name:string,color?:string,monsters:array<int,array{name:string,sprite_image:string,url:string}>>>}
    public function presentTierList(): array
    {
        $names = $this->opinionService->getAllOpinionNames();

        // Prepare grouping buckets in desired order
        $order = ['S', 'A', 'B', 'C', 'D'];
        $grouped = [];

        // Collect all valid Pokemon identifiers for batch fetching
        $validIdentifiers = [];
        $nameToRating = [];
        $nameToSpeciesName = [];

        foreach ($names as $name) {
            // For rating purposes, use the species name instead of the individual form name
            $speciesName = $this->extractSpeciesNameFromFormName($name);
            $ratingIdentifier = MonsterIdentifier::fromString($speciesName);

            $opinionResult = $this->opinionService->getOpinion($ratingIdentifier);
            if (!$opinionResult->isSuccess()) {
                continue;
            }
            $opinion = $opinionResult->getValue();
            $rating = strtoupper((string)($opinion['rating'] ?? ''));
            if ($rating === '') {
                continue;
            }

            // For fetching Pokemon data, use a canonical form name that exists in PokeAPI
            $fetchName = $this->getCanonicalFormName($name);
            $fetchIdentifier = MonsterIdentifier::fromString($fetchName);
            $validIdentifiers[] = $fetchIdentifier;

            // Map the canonical form name to the original species name for URL generation
            // and map the canonical form name to the rating for lookup
            $speciesNameForUrl = $speciesName;
            $nameToRating[$fetchName] = $rating;
            $nameToSpeciesName[$fetchName] = $speciesNameForUrl;
        }

        // Batch fetch all Pokemon data at once for optimal performance
        $monsterResults = $this->pokeapi->fetchMonstersBatch($validIdentifiers, null, $this->cacheTtl);

        // Process results and group by rating
        foreach ($validIdentifiers as $identifier) {
            $fetchName = $identifier->getValue();
            $rating = $nameToRating[$fetchName];
            $speciesNameForUrl = $nameToSpeciesName[$fetchName];

            $monsterResult = $monsterResults[$fetchName] ?? null;
            if (!$monsterResult || $monsterResult->isFailure()) {
                continue;
            }

            $monster = $monsterResult->getValue();
            $spriteUrl = 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/' . $monster->id . '.png';
            $grouped[$rating][] = [
                'name' => $monster->name,
                'sprite_image' => $spriteUrl,
                'url' => '/dex/' . mb_strtolower($speciesNameForUrl),
            ];
        }

        // Sort monsters within each tier alphabetically by name for stable output
        foreach ($grouped as &$monsters) {
            usort($monsters, function (array $a, array $b): int {
                return strcmp($a['name'], $b['name']);
            });
        }
        unset($monsters);

        // Build tiers array in order, skipping empty tiers
        $tiers = [];
        foreach ($order as $letter) {
            if (!isset($grouped[$letter]) || empty($grouped[$letter])) {
                continue;
            }
            $tiers[] = [
                'name' => $letter,
                'monsters' => $grouped[$letter],
            ];
        }

        return [
            'name' => "Jennifer's Pokémon Tierlist",
            'tiers' => $tiers,
        ];
    }

    //! @brief Extract species name from Pokemon form name for rating purposes
    //! @param name The Pokemon name (e.g., "maushold-family-of-four")
    //! @return string The species name (e.g., "maushold")
    private function extractSpeciesNameFromFormName(string $name): string
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
        return $name;
    }

    //! @brief Get the canonical form name for fetching Pokemon data from PokeAPI
    //! @param name The Pokemon name (e.g., "maushold" or "maushold-family-of-four")
    //! @return string The canonical form name that exists in PokeAPI (e.g., "maushold-family-of-four")
    private function getCanonicalFormName(string $name): string
    {
        $speciesName = $this->extractSpeciesNameFromFormName($name);

        // If the species name matches the original name, it's already a valid form
        if ($speciesName === $name) {
            return $name;
        }

        // For species that don't exist in PokeAPI, map to a canonical form
        $canonicalFormMapping = [
            // Maushold - use family-of-four as the canonical form
            'maushold' => 'maushold-family-of-four',

            // Add other species that need canonical forms here as needed
            // Example: 'deoxys' => 'deoxys-normal',
            // Example: 'arceus' => 'arceus-normal',
        ];

        // Check if we have a canonical form mapping for this species
        $lowerSpeciesName = mb_strtolower($speciesName);
        if (isset($canonicalFormMapping[$lowerSpeciesName])) {
            return $canonicalFormMapping[$lowerSpeciesName];
        }

        // Fallback: return the original name
        return $name;
    }
}


