<?php

declare(strict_types=1);

namespace App\Service;

use App\Type\Result;
use App\Type\MonsterData;
use App\Type\EvolutionData;
use App\Type\MonsterIdentifier;
use App\Type\MonsterType;
use App\Type\FilePath;
use App\Type\CacheVersion;
use App\Service\CacheKeys;

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
                ],
                'ssl' => [
                    'verify_peer' => false,      // Disable for development - should be true in production
                    'verify_peer_name' => false, // Disable for development - should be true in production
                ]
            ]);
            $result = @file_get_contents($url, false, $context);
            if ($result === false) {
                throw new \RuntimeException('Failed to fetch PokeAPI: ' . $url);
            }
            return $result;
        };
    }

    //! @brief Fetch multiple Pokemon monsters by identifiers in batch for better performance
    //! @param identifiers Array of MonsterIdentifier objects to fetch
    //! @param cache_dir Optional directory for file-based caching (defaults to system temp directory)
    //! @param ttl_seconds Time-to-live for cache entries in seconds (defaults to 300)
    //! @return array<string,Result<MonsterData>> Associative array mapping identifier values to Results
    public function fetchMonstersBatch(array $identifiers, ?FilePath $cache_dir = null, int $ttl_seconds = 300, CacheVersion $cache_version = CacheVersion::V1): array
    {
        $results = [];
        $cache_dir = $cache_dir ?? FilePath::fromString(sys_get_temp_dir() . '/pokeapi_cache');
        $cache_dir->ensureDirectoryExists();

        // First, check which Pokemon are already cached and fresh
        $uncached_identifiers = [];
        foreach ($identifiers as $identifier) {
            $id_or_name = $identifier->getValue();
            $cache_file = CacheKeys::pokemonForIdentifier($cache_dir, $cache_version, $identifier);

            if ($cache_file->exists() && $ttl_seconds > 0 && !$cache_file->isOlderThan($ttl_seconds)) {
                // Use cached data
                try {
                    $json = $cache_file->readContents();
                    $monsterData = $this->parseMonsterJson($json, $cache_dir, $ttl_seconds, $cache_version);
                    $results[$id_or_name] = Result::success($monsterData);
                } catch (\Throwable $e) {
                    // Cache file corrupted, fetch fresh
                    $uncached_identifiers[] = $identifier;
                }
            } else {
                $uncached_identifiers[] = $identifier;
            }
        }

        // Fetch uncached Pokemon in parallel using concurrent HTTP requests
        if (!empty($uncached_identifiers)) {
            $batch_results = $this->fetchUncachedMonstersBatch($uncached_identifiers, $cache_dir, $ttl_seconds, $cache_version);
            $results = array_merge($results, $batch_results);
        }

        return $results;
    }

    //! @brief Fetch a Pokemon monster by identifier and return as MonsterData
    //! @param identifier MonsterIdentifier containing ID or name (e.g., "25" or "pikachu")
    //! @param cache_dir Optional directory for file-based caching (defaults to system temp directory)
    //! @param ttl_seconds Time-to-live for cache entries in seconds (defaults to 300)
    //! @return Result<MonsterData> Success containing MonsterData, or failure with error message
    public function fetchMonster(MonsterIdentifier $identifier, ?FilePath $cache_dir = null, int $ttl_seconds = 300, CacheVersion $cache_version = CacheVersion::V1): Result
    {
        $id_or_name = $identifier->getValue();

        // Try the original identifier first
        $result = $this->fetchMonsterByIdentifier($identifier, $cache_dir, $ttl_seconds, $cache_version);

        // If that fails and it's a name (not numeric ID), try variant forms
        if ($result->isFailure() && $identifier->isName()) {
            $variant_identifiers = $this->getVariantIdentifiers($id_or_name);
            foreach ($variant_identifiers as $variant_identifier) {
                $variant_result = $this->fetchMonsterByIdentifier($variant_identifier, $cache_dir, $ttl_seconds, $cache_version);
                if ($variant_result->isSuccess()) {
                    return $variant_result;
                }
            }
        }

        return $result;
    }

    //! @brief Fetch a Pokemon monster by specific identifier without variant fallback
    //! @param identifier MonsterIdentifier containing ID or name
    //! @param cache_dir Optional directory for file-based caching
    //! @param ttl_seconds Time-to-live for cache entries in seconds
    //! @return Result<MonsterData> Success containing MonsterData, or failure with error message
    private function fetchMonsterByIdentifier(MonsterIdentifier $identifier, ?FilePath $cache_dir, int $ttl_seconds, CacheVersion $cache_version): Result
    {
        $id_or_name = $identifier->getValue();
        $url = 'https://pokeapi.co/api/v2/pokemon/' . rawurlencode($id_or_name);

        // Simple file-based cache
        $cache_dir = $cache_dir ?? FilePath::fromString(sys_get_temp_dir() . '/pokeapi_cache');
        $cache_dir->ensureDirectoryExists();
        $cache_file = CacheKeys::pokemonForIdentifier($cache_dir, $cache_version, $identifier);

        $json = null;
        $is_fresh_cache = false;
        if ($cache_file->exists() && $ttl_seconds > 0 && !$cache_file->isOlderThan($ttl_seconds)) {
            $json = $cache_file->readContents();
            $is_fresh_cache = true;
        }

        if ($json === null) {
            try {
                $json = ($this->http_client)($url);
                // Write/refresh cache on success
                $cache_file->writeContents($json);
            } catch (\Throwable $e) {
                // On network failure: fall back to stale cache if present
                if ($cache_file->exists()) {
                    try {
                        $json = $cache_file->readContents();
                    } catch (\RuntimeException $readError) {
                        $json = null;
                    }
                }
                if ($json === null) {
                    return Result::failure('Failed to fetch Pokemon data: ' . $e->getMessage());
                }
            }
        }

        try {
            $monsterData = $this->parseMonsterJson($json, $cache_dir, $ttl_seconds, $cache_version);
            return Result::success($monsterData);
        } catch (\JsonException $e) {
            return Result::failure('Invalid JSON response: ' . $e->getMessage());
        } catch (\InvalidArgumentException $e) {
            return Result::failure('Invalid Pokemon data: ' . $e->getMessage());
        } catch (\Throwable $e) {
            return Result::failure('Failed to parse Pokemon data: ' . $e->getMessage());
        }
    }

    //! @brief Get variant identifiers for Pokemon that have multiple forms
    //! @param name The base Pokemon name
    //! @return MonsterIdentifier[] Array of variant identifiers to try
    private function getVariantIdentifiers(string $name): array
    {
        $variants = [];

        // Handle Maushold variants
        if (strtolower($name) === 'maushold') {
            $variants[] = MonsterIdentifier::fromString('maushold-family-of-four');
            $variants[] = MonsterIdentifier::fromString('maushold-family-of-three');
        }

        // Add more Pokemon variants here as needed
        // Example: if (strtolower($name) === 'deoxys') {
        //     $variants[] = MonsterIdentifier::fromString('deoxys-normal');
        //     $variants[] = MonsterIdentifier::fromString('deoxys-attack');
        //     $variants[] = MonsterIdentifier::fromString('deoxys-defense');
        //     $variants[] = MonsterIdentifier::fromString('deoxys-speed');
        // }

        return $variants;
    }

    //! @brief Fetch uncached Pokemon monsters in batch using concurrent HTTP requests
    //! @param identifiers Array of MonsterIdentifier objects that need to be fetched
    //! @param cache_dir Cache directory for storing fetched data
    //! @param ttl_seconds Time-to-live for cache entries
    //! @return array<string,Result<MonsterData>> Associative array mapping identifier values to Results
    private function fetchUncachedMonstersBatch(array $identifiers, FilePath $cache_dir, int $ttl_seconds, CacheVersion $cache_version): array
    {
        $results = [];
        $urls = [];
        $identifier_map = [];

        // Prepare URLs and mapping
        foreach ($identifiers as $identifier) {
            $id_or_name = $identifier->getValue();
            $url = 'https://pokeapi.co/api/v2/pokemon/' . rawurlencode($id_or_name);
            $urls[] = $url;
            $identifier_map[$url] = $identifier;
        }

        // Fetch all URLs concurrently
        $responses = $this->fetchUrlsConcurrently($urls);

        // Process responses and cache results
        foreach ($responses as $url => $response) {
            $identifier = $identifier_map[$url];
            $id_or_name = $identifier->getValue();
            $cache_file = CacheKeys::pokemonForIdentifier($cache_dir, $cache_version, $identifier);

            if ($response['success']) {
                try {
                    // Write to cache
                    $cache_file->writeContents($response['data']);

                    // Parse and return MonsterData
                    $monsterData = $this->parseMonsterJson($response['data'], $cache_dir, $ttl_seconds, $cache_version);
                    $results[$id_or_name] = Result::success($monsterData);
                } catch (\Throwable $e) {
                    $results[$id_or_name] = Result::failure('Failed to parse Pokemon data: ' . $e->getMessage());
                }
            } else {
                // Try to use stale cache if available
                if ($cache_file->exists()) {
                    try {
                        $json = $cache_file->readContents();
                        $monsterData = $this->parseMonsterJson($json, $cache_dir, $ttl_seconds, $cache_version);
                        $results[$id_or_name] = Result::success($monsterData);
                    } catch (\Throwable $e) {
                        $results[$id_or_name] = Result::failure('Failed to fetch Pokemon data: ' . $response['error']);
                    }
                } else {
                    $results[$id_or_name] = Result::failure('Failed to fetch Pokemon data: ' . $response['error']);
                }
            }
        }

        return $results;
    }

    //! @brief Fetch multiple URLs concurrently using multi-handle cURL
    //! @param urls Array of URLs to fetch
    //! @return array<string,array{success:bool,data?:string,error?:string}> Associative array mapping URLs to response data
    private function fetchUrlsConcurrently(array $urls): array
    {
        $results = [];

        // Initialize cURL multi-handle
        $multi_handle = curl_multi_init();
        $curl_handles = [];

        // Create individual cURL handles
        foreach ($urls as $url) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_USERAGENT => 'PHP',
                CURLOPT_HTTPHEADER => ['Accept: application/json'],
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => false, // Disable for development - should be true in production
                CURLOPT_SSL_VERIFYHOST => 0,     // Disable for development - should be 2 in production
                CURLOPT_MAXREDIRS => 3,
            ]);

            curl_multi_add_handle($multi_handle, $ch);
            $curl_handles[$url] = $ch;
        }

        // Execute all requests with better error handling
        $running = null;
        $max_attempts = 3;
        $attempt = 0;

        do {
            $status = curl_multi_exec($multi_handle, $running);
            if ($status === CURLM_CALL_MULTI_PERFORM) {
                continue;
            }

            if ($status !== CURLM_OK) {
                $attempt++;
                if ($attempt >= $max_attempts) {
                    break;
                }
                usleep(100000); // Wait 100ms before retry
                continue;
            }

            // Check for completed requests
            while ($info = curl_multi_info_read($multi_handle)) {
                if ($info['msg'] === CURLMSG_DONE) {
                    $ch = $info['handle'];
                    $url = array_search($ch, $curl_handles, true);

                    if ($url !== false) {
                        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        $data = curl_multi_getcontent($ch);
                        $error = curl_error($ch);

                        if ($error !== '') {
                            $results[$url] = ['success' => false, 'error' => $error];
                        } elseif ($http_code >= 200 && $http_code < 300 && $data !== false && $data !== '') {
                            $results[$url] = ['success' => true, 'data' => $data];
                        } else {
                            $results[$url] = ['success' => false, 'error' => "HTTP $http_code" . ($data === '' ? ' (empty response)' : '')];
                        }
                    }
                }
            }

            if ($running > 0) {
                curl_multi_select($multi_handle, 1.0);
            }
        } while ($running > 0);

        // Clean up any remaining handles
        foreach ($curl_handles as $url => $ch) {
            if (!isset($results[$url])) {
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $data = curl_multi_getcontent($ch);
                $error = curl_error($ch);

                if ($error !== '') {
                    $results[$url] = ['success' => false, 'error' => $error];
                } elseif ($http_code >= 200 && $http_code < 300 && $data !== false && $data !== '') {
                    $results[$url] = ['success' => true, 'data' => $data];
                } else {
                    $results[$url] = ['success' => false, 'error' => "HTTP $http_code" . ($data === '' ? ' (empty response)' : '')];
                }
            }

            curl_multi_remove_handle($multi_handle, $ch);
            curl_close($ch);
        }

        curl_multi_close($multi_handle);

        return $results;
    }

    //! @brief Parse Pokemon JSON data into MonsterData object
    //! @param json Raw JSON string from PokeAPI
    //! @param cache_dir Cache directory for evolution chain caching
    //! @param ttl_seconds Time-to-live for cache entries
    //! @return MonsterData Parsed MonsterData object
    //! @throws \JsonException If JSON parsing fails
    //! @throws \InvalidArgumentException If required data is missing
    private function parseMonsterJson(string $json, FilePath $cache_dir, int $ttl_seconds, CacheVersion $cache_version): MonsterData
    {
        $data = json_decode($json, true, flags: JSON_THROW_ON_ERROR); /** @var array<string,mixed> $data */


        $types = $data['types'] ?? [];
        usort($types, function ($a, $b) {
            return ($a['slot'] ?? 0) <=> ($b['slot'] ?? 0);
        });

        $type1String = $types[0]['type']['name'] ?? '';
        $type2String = isset($types[1]) ? ($types[1]['type']['name'] ?? null) : null;

        if (empty($type1String)) {
            throw new \InvalidArgumentException('No primary type found for Pokemon');
        }

        $type1 = MonsterType::fromString($type1String);
        $type2 = $type2String ? MonsterType::fromString($type2String) : null;

        $image = $data['sprites']['other']['official-artwork']['front_default']
            ?? $data['sprites']['front_default']
            ?? '';

        // Fetch evolution chain data
        $currentPokemonName = (string)($data['name'] ?? '');
        $speciesUrl = $data['species']['url'] ?? '';
        $evolutionResult = $this->fetchEvolutionChain($speciesUrl, $currentPokemonName, $cache_dir, $ttl_seconds, $cache_version);

        $precursor = null;
        $successors = [];
        $speciesName = null;
        if ($evolutionResult->isSuccess()) {
            $evolutionData = $evolutionResult->getValue();
            $precursor = $evolutionData['precursor'] ?? null;
            $successors = $evolutionData['successors'] ?? [];
            $speciesName = $evolutionData['species_name'] ?? null;
        }


        $monsterData = new MonsterData(
            id: (int)($data['id'] ?? 0),
            name: self::titleCase((string)($data['name'] ?? '')),
            image: (string)$image,
            type1: $type1,
            type2: $type2,
            precursor: $precursor,
            successors: $successors,
            height: $cache_version === CacheVersion::V2 ? (isset($data['height']) ? (int)$data['height'] : null) : null,
            weight: $cache_version === CacheVersion::V2 ? (isset($data['weight']) ? (int)$data['weight'] : null) : null,
            speciesName: $speciesName
        );

        // Cache aliasing for both numeric ID and canonical name
        try {
            $numericId = isset($data['id']) ? (string)$data['id'] : '';
            $canonicalName = isset($data['name']) ? (string)$data['name'] : '';

            if ($numericId !== '') {
                $idCache = CacheKeys::pokemonById($cache_dir, $cache_version, $numericId);
                if (!$idCache->exists()) {
                    $idCache->writeContents($json);
                }
            }

            if ($canonicalName !== '') {
                $nameKey = mb_strtolower(trim($canonicalName));
                $nameCache = CacheKeys::pokemonByName($cache_dir, $cache_version, $nameKey);
                if (!$nameCache->exists()) {
                    $nameCache->writeContents($json);
                }
            }
        } catch (\Throwable $e) {
            // Best-effort aliasing; ignore failures silently
        }

        return $monsterData;
    }

    //! @brief Fetch evolution chain data for a Pokemon from its species URL
    //! @param species_url URL to the Pokemon species endpoint from PokeAPI
    //! @param current_pokemon_name Name of the current Pokemon to find in the evolution chain
    //! @param cache_dir Cache directory for storing evolution data
    //! @param ttl_seconds Time-to-live for evolution data cache entries
    //! @return Result<array{species_name:string,precursor?:EvolutionData,successors:EvolutionData[]}> Success with evolution data or failure
    private function fetchEvolutionChain(string $species_url, string $current_pokemon_name, ?FilePath $cache_dir, int $ttl_seconds, CacheVersion $cache_version): Result
    {
        if (empty($species_url)) {
            return Result::failure('No species URL provided');
        }

        $cache_dir = $cache_dir ?? FilePath::fromString(sys_get_temp_dir() . '/pokeapi_cache');
        $cache_file = CacheKeys::species($cache_dir, $cache_version, $species_url);

        $json = null;
        if ($cache_file->exists() && $ttl_seconds > 0 && !$cache_file->isOlderThan($ttl_seconds)) {
            $json = $cache_file->readContents();
        }

        if ($json === null) {
            try {
                $json = ($this->http_client)($species_url);
                $cache_file->writeContents($json);
            } catch (\Throwable $e) {
                if ($cache_file->exists()) {
                    $json = $cache_file->readContents() ?: null;
                }
                if ($json === null) {
                    return Result::failure('Failed to fetch species data: ' . $e->getMessage());
                }
            }
        }

        try {
            $speciesData = json_decode($json, true, flags: JSON_THROW_ON_ERROR); /** @var array<string,mixed> $speciesData */
            $speciesName = self::titleCase((string)($speciesData['name'] ?? ''));
            $evolutionChainUrl = $speciesData['evolution_chain']['url'] ?? '';

            if (empty($evolutionChainUrl)) {
                return Result::failure('No evolution chain URL found');
            }

            $evolutionResult = $this->parseEvolutionChain($evolutionChainUrl, $current_pokemon_name, $cache_dir, $ttl_seconds, $cache_version);
            if ($evolutionResult->isFailure()) {
                return $evolutionResult;
            }

            $evolutionData = $evolutionResult->getValue();
            $evolutionData['species_name'] = $speciesName;

            return Result::success($evolutionData);
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
    private function parseEvolutionChain(string $evolution_chain_url, string $current_pokemon_name, ?FilePath $cache_dir, int $ttl_seconds, CacheVersion $cache_version): Result
    {
        $cache_dir = $cache_dir ?? FilePath::fromString(sys_get_temp_dir() . '/pokeapi_cache');
        $cache_file = CacheKeys::evolutionChain($cache_dir, $cache_version, $evolution_chain_url);

        $json = null;
        if ($cache_file->exists() && $ttl_seconds > 0 && !$cache_file->isOlderThan($ttl_seconds)) {
            $json = $cache_file->readContents();
        }

        if ($json === null) {
            try {
                $json = ($this->http_client)($evolution_chain_url);
                $cache_file->writeContents($json);
            } catch (\Throwable $e) {
                if ($cache_file->exists()) {
                    $json = $cache_file->readContents() ?: null;
                }
                if ($json === null) {
                    return Result::failure('Failed to fetch evolution chain: ' . $e->getMessage());
                }
            }
        }

        try {
            $chainData = json_decode($json, true, flags: JSON_THROW_ON_ERROR); /** @var array<string,mixed> $chainData */
            $chain = $chainData['chain'] ?? [];

            // Seed species cache entries for all species in this chain so that
            // subsequent navigations to related evolutions can reuse local cache
            // without hitting the species endpoint again.
            try {
                $this->seedSpeciesCachesFromChain(
                    $chain,
                    (string)($chainData['id'] ?? '') !== '' ? $evolution_chain_url : $evolution_chain_url,
                    $cache_dir,
                    $cache_version
                );
            } catch (\Throwable $e) {
                // Best-effort seeding; ignore failures
            }

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

    //! @brief Seed species cache files for all species found in an evolution chain
    //! @details Writes minimal JSON containing the evolution_chain url so later lookups
    //! of any species in the chain can avoid an immediate network call.
    //! @param chain The evolution chain data array
    //! @param evolution_chain_url URL of the evolution chain endpoint
    //! @param cache_dir Cache directory for storing species data
    //! @param cache_version Cache version for versioning
    private function seedSpeciesCachesFromChain(array $chain, string $evolution_chain_url, FilePath $cache_dir, CacheVersion $cache_version): void
    {
        $queue = [$chain];
        while (!empty($queue)) {
            $node = array_shift($queue); /** @var array<string,mixed> $node */
            $speciesUrl = (string)($node['species']['url'] ?? '');
            if ($speciesUrl !== '') {
                $speciesCache = CacheKeys::species($cache_dir, $cache_version, $speciesUrl);
                if (!$speciesCache->exists()) {
                    $speciesCache->writeContents(json_encode([
                        'evolution_chain' => ['url' => $evolution_chain_url]
                    ]));
                }
            }
            $children = isset($node['evolves_to']) && is_array($node['evolves_to']) ? $node['evolves_to'] : [];
            foreach ($children as $child) {
                $queue[] = $child;
            }
        }
    }
}


