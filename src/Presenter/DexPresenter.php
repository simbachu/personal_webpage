<?php

declare(strict_types=1);

namespace App\Presenter;

use App\Service\PokeApiService;
use App\Service\PokemonOpinionService;
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
    private PokemonOpinionService $opinionService; //!< Service for fetching pokemon opinions
    private int $cacheTtl; //!< Cache TTL in seconds

    //! @brief Construct a new DexPresenter instance
    //! @param pokeapi PokeAPI service for fetching Pokemon data
    //! @param opinionService Pokemon opinion service for fetching opinions
    //! @param cacheTtl Cache time-to-live in seconds for Pokemon data (defaults to 300)
    public function __construct(PokeApiService $pokeapi, PokemonOpinionService $opinionService, int $cacheTtl = 300)
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
        $identifier = MonsterIdentifier::fromString((string)$monsterData->id);
        $opinionResult = $this->opinionService->getOpinion($identifier);

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

        foreach ($names as $name) {
            $identifier = MonsterIdentifier::fromString($name);
            $opinionResult = $this->opinionService->getOpinion($identifier);
            if (!$opinionResult->isSuccess()) {
                continue;
            }
            $opinion = $opinionResult->getValue();
            $rating = strtoupper((string)($opinion['rating'] ?? ''));
            if ($rating === '') {
                continue;
            }

            $validIdentifiers[] = $identifier;
            $nameToRating[$name] = $rating;
        }

        // Batch fetch all Pokemon data at once for optimal performance
        $monsterResults = $this->pokeapi->fetchMonstersBatch($validIdentifiers, null, $this->cacheTtl);

        // Process results and group by rating
        foreach ($validIdentifiers as $identifier) {
            $name = $identifier->getValue();
            $rating = $nameToRating[$name];

            $monsterResult = $monsterResults[$name] ?? null;
            if (!$monsterResult || $monsterResult->isFailure()) {
                continue;
            }

            $monster = $monsterResult->getValue();
            $spriteUrl = 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/' . $monster->id . '.png';
            $grouped[$rating][] = [
                'name' => $monster->name,
                'sprite_image' => $spriteUrl,
                'url' => '/dex/' . mb_strtolower($name),
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
}


