<?php

declare(strict_types=1);

namespace App\Service;

use App\Type\Result;
use App\Type\MonsterData;
use App\Type\EvolutionData;
use App\Type\MonsterIdentifier;
use App\Type\MonsterType;

//! @brief Service for fetching Pokemon data from the PokeAPI with caching and error handling
//!
//! This service provides methods to fetch Pokemon data from the PokeAPI (https://pokeapi.co/)
//! with built-in caching, error handling using Result types, and evolution chain support.
//! The HTTP client is injectable for improved testability.
//!
//! @code
//! // Example usage:
//! $service = new PokeApiService();
//! $result = $service->fetchMonster('pikachu');
//!
//! if ($result->isSuccess()) {
//!     $monster = $result->getValue();
//!     echo $monster->name; // "Pikachu"
//! } else {
//!     echo "Error: " . $result->getError();
//! }
//! @endcode
class PokeApiService
{
    /** @var callable(string):string */
    private $http_client; //!< Simple HTTP client callback returning raw JSON

    //! @brief Construct a new PokeApiService instance
    //! @param http_client Optional callable that takes a URL and returns JSON string. If null, uses default HTTP client
    public function __construct(?callable $http_client = null)
    {
        $this->http_client = $http_client ?? function (string $url): string {
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => [
                        'User-Agent: PHP',
                        'Accept: application/json'
                    ],
                    'timeout' => 5,
                ]
            ]);
            $result = @file_get_contents($url, false, $context);
            if ($result === false) {
                throw new \RuntimeException('Failed to fetch PokeAPI: ' . $url);
            }
            return $result;
        };
    }

    //! @brief Fetch a Pokemon monster by identifier and return as MonsterData
    //! @param identifier MonsterIdentifier containing ID or name (e.g., "25" or "pikachu")
    //! @param cache_dir Optional directory for file-based caching (defaults to system temp directory)
    //! @param ttl_seconds Time-to-live for cache entries in seconds (defaults to 300)
    //! @return Result<MonsterData> Success containing MonsterData, or failure with error message
    public function fetchMonster(MonsterIdentifier $identifier, ?string $cache_dir = null, int $ttl_seconds = 300): Result
    {
        $id_or_name = $identifier->getValue();
        $url = 'https://pokeapi.co/api/v2/pokemon/' . rawurlencode($id_or_name);

        // Simple file-based cache
        $cache_dir = $cache_dir ?? (sys_get_temp_dir() . '/pokeapi_cache');
        if (!is_dir($cache_dir)) {
            @mkdir($cache_dir, 0777, true);
        }
        $cache_file = $cache_dir . '/pokemon_' . md5($id_or_name) . '.json';

        $json = null;
        $is_fresh_cache = false;
        if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $ttl_seconds) {
            $json = file_get_contents($cache_file) ?: null;
            $is_fresh_cache = true;
        }

        if ($json === null) {
            try {
                $json = ($this->http_client)($url);
                // Write/refresh cache on success
                @file_put_contents($cache_file, $json);
            } catch (\Throwable $e) {
                // On network failure: fall back to stale cache if present
                if (file_exists($cache_file)) {
                    $json = file_get_contents($cache_file) ?: null;
                }
                if ($json === null) {
                    return Result::failure('Failed to fetch Pokemon data: ' . $e->getMessage());
                }
            }
        }

        try {
            /** @var array<string,mixed> $data */
            $data = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return Result::failure('Invalid JSON response: ' . $e->getMessage());
        }

        $types = $data['types'] ?? [];
        usort($types, function ($a, $b) {
            return ($a['slot'] ?? 0) <=> ($b['slot'] ?? 0);
        });

        $type1String = $types[0]['type']['name'] ?? '';
        $type2String = isset($types[1]) ? ($types[1]['type']['name'] ?? null) : null;

        if (empty($type1String)) {
            return Result::failure('No primary type found for Pokemon');
        }

        try {
            $type1 = MonsterType::fromString($type1String);
            $type2 = $type2String ? MonsterType::fromString($type2String) : null;
        } catch (\InvalidArgumentException $e) {
            return Result::failure('Invalid Pokemon type: ' . $e->getMessage());
        }

        $image = $data['sprites']['other']['official-artwork']['front_default']
            ?? $data['sprites']['front_default']
            ?? '';

        // Fetch evolution chain data
        $currentPokemonName = (string)($data['name'] ?? '');
        $speciesUrl = $data['species']['url'] ?? '';
        $evolutionResult = $this->fetchEvolutionChain($speciesUrl, $currentPokemonName, $cache_dir, $ttl_seconds);

        $precursor = null;
        $successors = [];
        if ($evolutionResult->isSuccess()) {
            $evolutionData = $evolutionResult->getValue();
            $precursor = $evolutionData['precursor'] ?? null;
            $successors = $evolutionData['successors'] ?? [];
        }

        $monsterData = new MonsterData(
            id: (int)($data['id'] ?? 0),
            name: self::titleCase((string)($data['name'] ?? '')),
            image: (string)$image,
            type1: $type1,
            type2: $type2,
            precursor: $precursor,
            successors: $successors
        );

        return Result::success($monsterData);
    }

    //! @brief Fetch evolution chain data for a Pokemon from its species URL
    //! @param species_url URL to the Pokemon species endpoint from PokeAPI
    //! @param current_pokemon_name Name of the current Pokemon to find in the evolution chain
    //! @param cache_dir Cache directory for storing evolution data
    //! @param ttl_seconds Time-to-live for evolution data cache entries
    //! @return Result<array{precursor?:EvolutionData,successors:EvolutionData[]}> Success with evolution data or failure
    private function fetchEvolutionChain(string $species_url, string $current_pokemon_name, ?string $cache_dir, int $ttl_seconds): Result
    {
        if (empty($species_url)) {
            return Result::failure('No species URL provided');
        }

        $cache_dir = $cache_dir ?? (sys_get_temp_dir() . '/pokeapi_cache');
        $cache_file = $cache_dir . '/evolution_' . md5($species_url) . '.json';

        $json = null;
        if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $ttl_seconds) {
            $json = file_get_contents($cache_file) ?: null;
        }

        if ($json === null) {
            try {
                $json = ($this->http_client)($species_url);
                @file_put_contents($cache_file, $json);
            } catch (\Throwable $e) {
                if (file_exists($cache_file)) {
                    $json = file_get_contents($cache_file) ?: null;
                }
                if ($json === null) {
                    return Result::failure('Failed to fetch species data: ' . $e->getMessage());
                }
            }
        }

        try {
            /** @var array<string,mixed> $speciesData */
            $speciesData = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
            $evolutionChainUrl = $speciesData['evolution_chain']['url'] ?? '';

            if (empty($evolutionChainUrl)) {
                return Result::failure('No evolution chain URL found');
            }

            return $this->parseEvolutionChain($evolutionChainUrl, $current_pokemon_name, $cache_dir, $ttl_seconds);
        } catch (\JsonException $e) {
            return Result::failure('Invalid species JSON: ' . $e->getMessage());
        } catch (\Throwable $e) {
            return Result::failure('Failed to parse species data: ' . $e->getMessage());
        }
    }

    //! @brief Parse evolution chain data to find precursor and successor relationships
    //! @param evolution_chain_url URL to the evolution chain endpoint from PokeAPI
    //! @param current_pokemon_name Name of the current Pokemon to locate in the evolution chain
    //! @param cache_dir Cache directory for storing parsed evolution chain data
    //! @param ttl_seconds Time-to-live for evolution chain cache entries
    //! @return Result<array{precursor?:EvolutionData,successors:EvolutionData[]}> Success with evolution data or failure
    private function parseEvolutionChain(string $evolution_chain_url, string $current_pokemon_name, ?string $cache_dir, int $ttl_seconds): Result
    {
        $cache_dir = $cache_dir ?? (sys_get_temp_dir() . '/pokeapi_cache');
        $cache_file = $cache_dir . '/evolution_chain_' . md5($evolution_chain_url) . '.json';

        $json = null;
        if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $ttl_seconds) {
            $json = file_get_contents($cache_file) ?: null;
        }

        if ($json === null) {
            try {
                $json = ($this->http_client)($evolution_chain_url);
                @file_put_contents($cache_file, $json);
            } catch (\Throwable $e) {
                if (file_exists($cache_file)) {
                    $json = file_get_contents($cache_file) ?: null;
                }
                if ($json === null) {
                    return Result::failure('Failed to fetch evolution chain: ' . $e->getMessage());
                }
            }
        }

        try {
            /** @var array<string,mixed> $chainData */
            $chainData = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
            $chain = $chainData['chain'] ?? [];

            return $this->parseEvolutionChainData($chain, $current_pokemon_name);
        } catch (\JsonException $e) {
            return Result::failure('Invalid evolution chain JSON: ' . $e->getMessage());
        } catch (\Throwable $e) {
            return Result::failure('Failed to parse evolution chain: ' . $e->getMessage());
        }
    }

    //! @brief Parse evolution chain data to extract precursor and successors for a specific Pokemon
    //! @param chain The evolution chain data array from PokeAPI
    //! @param current_pokemon_name Name of the current Pokemon to find in the chain
    //! @return Result<array{precursor?:EvolutionData,successors:EvolutionData[]}> Success with evolution data or failure
    private function parseEvolutionChainData(array $chain, string $current_pokemon_name): Result
    {
        try {
            // Recursively search the evolution chain to find the current Pokemon and its relationships
            $result = $this->findPokemonInChain($chain, $current_pokemon_name);
            if (empty($result)) {
                return Result::failure('Pokemon not found in evolution chain');
            }
            return Result::success($result);
        } catch (\Throwable $e) {
            return Result::failure('Failed to parse evolution chain data: ' . $e->getMessage());
        }
    }

    //! @brief Recursively search evolution chain to find precursor and successors for a target Pokemon
    //! @param chain Current evolution chain level to search
    //! @param target_pokemon Name of the Pokemon to find in the evolution chain
    //! @param precursor Current precursor EvolutionData (what the target evolves from)
    //! @return array{precursor?:EvolutionData,successors:EvolutionData[]}|null Evolution data array with precursor and successors, or null if not found
    private function findPokemonInChain(array $chain, string $target_pokemon, ?EvolutionData $precursor = null): ?array
    {
        $currentSpecies = $chain['species']['name'] ?? '';

        // Check if this is the Pokemon we're looking for
        if ($currentSpecies === $target_pokemon) {
            $result = [];

            // Add precursor if we have one
            if ($precursor) {
                $result['precursor'] = $precursor;
            }

            // Add all successors (handle multiple evolutions like Eevee)
            $evolvesTo = $chain['evolves_to'] ?? [];
            $successors = [];
            foreach ($evolvesTo as $evolution) {
                if (isset($evolution['species']['name'])) {
                    $successorName = $evolution['species']['name'];
                    $successors[] = new EvolutionData(
                        name: self::titleCase($successorName),
                        url: '/dex/' . $successorName
                    );
                }
            }
            $result['successors'] = $successors;

            return $result;
        }

        // Recursively search in evolution branches
        $evolvesTo = $chain['evolves_to'] ?? [];
        foreach ($evolvesTo as $evolution) {
            $currentPrecursor = new EvolutionData(
                name: self::titleCase($currentSpecies),
                url: '/dex/' . $currentSpecies
            );
            $found = $this->findPokemonInChain($evolution, $target_pokemon, $currentPrecursor);
            if ($found) {
                return $found;
            }
        }

        return null;
    }

    //! @brief Convert a string to title case (first letter capitalized)
    //! @param name Input string to convert to title case
    //! @return string String with first letter capitalized and rest lowercase
    private static function titleCase(string $name): string
    {
        if ($name === '') {
            return $name;
        }
        return mb_strtoupper(mb_substr($name, 0, 1)) . mb_strtolower(mb_substr($name, 1));
    }
}


