<?php

declare(strict_types=1);

namespace App\Service;

//! @brief Minimal PokeAPI service with injectable HTTP client for testability
class PokeApiService
{
    /** @var callable(string):string */
    private $http_client; //!< Simple HTTP client callback returning raw JSON

    //! @brief Constructor
    //! @param http_client Callable that takes URL and returns JSON string
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

    //! @brief Fetch a monster by id or name and map to monster view model
    //! @param id_or_name Monster id or name
    //! @param cache_dir Optional directory for file cache (defaults to sys temp)
    //! @param ttl_seconds Time-to-live for cache in seconds (defaults to 300)
    //! @return array{id:int,name:string,image:string,type1:string,type2?:string,precursor?:array{name:string,url:string},successor?:array{name:string,url:string}}
    public function fetchMonster(string $id_or_name, ?string $cache_dir = null, int $ttl_seconds = 300): array
    {
        $id_or_name = trim($id_or_name);
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
                    throw $e;
                }
            }
        }
        /** @var array<string,mixed> $data */
        $data = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

        $types = $data['types'] ?? [];
        usort($types, function ($a, $b) {
            return ($a['slot'] ?? 0) <=> ($b['slot'] ?? 0);
        });

        $type1 = $types[0]['type']['name'] ?? '';
        $type2 = isset($types[1]) ? ($types[1]['type']['name'] ?? null) : null;

        $image = $data['sprites']['other']['official-artwork']['front_default']
            ?? $data['sprites']['front_default']
            ?? '';

        $monster = [
            'id' => (int)($data['id'] ?? 0),
            'name' => self::titleCase((string)($data['name'] ?? '')),
            'image' => (string)$image,
            'type1' => (string)$type1,
        ];

        if ($type2) {
            $monster['type2'] = (string)$type2;
        }

        // Fetch evolution chain data
        $currentPokemonName = (string)($data['name'] ?? '');
        $speciesUrl = $data['species']['url'] ?? '';
        $evolutionData = $this->fetchEvolutionChain($speciesUrl, $currentPokemonName, $cache_dir, $ttl_seconds);
        if ($evolutionData) {
            $monster = array_merge($monster, $evolutionData);
        }

        return $monster;
    }

    //! @brief Fetch evolution chain data for a Pokemon
    //! @param species_url URL to the species endpoint
    //! @param current_pokemon_name Name of the current Pokemon
    //! @param cache_dir Cache directory
    //! @param ttl_seconds Cache TTL
    //! @return array{precursor?:array{name:string,url:string},successor?:array{name:string,url:string}}|null
    private function fetchEvolutionChain(string $species_url, string $current_pokemon_name, ?string $cache_dir, int $ttl_seconds): ?array
    {
        if (empty($species_url)) {
            return null;
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
                    return null; // Fail silently for evolution data
                }
            }
        }

        try {
            /** @var array<string,mixed> $speciesData */
            $speciesData = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
            $evolutionChainUrl = $speciesData['evolution_chain']['url'] ?? '';

            if (empty($evolutionChainUrl)) {
                return null;
            }

            return $this->parseEvolutionChain($evolutionChainUrl, $current_pokemon_name, $cache_dir, $ttl_seconds);
        } catch (\Throwable $e) {
            return null; // Fail silently for evolution data
        }
    }

    //! @brief Parse evolution chain to find precursor and successor
    //! @param evolution_chain_url URL to evolution chain endpoint
    //! @param current_pokemon_name Name of the current Pokemon
    //! @param cache_dir Cache directory
    //! @param ttl_seconds Cache TTL
    //! @return array{precursor?:array{name:string,url:string},successor?:array{name:string,url:string}}|null
    private function parseEvolutionChain(string $evolution_chain_url, string $current_pokemon_name, ?string $cache_dir, int $ttl_seconds): ?array
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
                    return null;
                }
            }
        }

        try {
            /** @var array<string,mixed> $chainData */
            $chainData = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
            $chain = $chainData['chain'] ?? [];

            return $this->parseEvolutionChainData($chain, $current_pokemon_name);
        } catch (\Throwable $e) {
            return null;
        }
    }

    //! @brief Parse evolution chain data to find precursor and successor relationships
    //! @param array $chain Evolution chain data from PokeAPI
    //! @param string $current_pokemon_name Name of the current Pokemon
    //! @return array{precursor?:array{name:string,url:string},successor?:array{name:string,url:string}}|null
    private function parseEvolutionChainData(array $chain, string $current_pokemon_name): ?array
    {
        // Recursively search the evolution chain to find the current Pokemon and its relationships
        $result = $this->findPokemonInChain($chain, $current_pokemon_name);
        return empty($result) ? null : $result;
    }

    //! @brief Recursively find a Pokemon in the evolution chain and return its relationships
    //! @param array $chain Current chain node
    //! @param string $target_pokemon Name of Pokemon to find
    //! @param array|null $precursor Parent Pokemon (for precursor relationship)
    //! @return array{precursor?:array{name:string,url:string},successor?:array{name:string,url:string}}|null
    private function findPokemonInChain(array $chain, string $target_pokemon, ?array $precursor = null): ?array
    {
        $currentSpecies = $chain['species']['name'] ?? '';

        // Check if this is the Pokemon we're looking for
        if ($currentSpecies === $target_pokemon) {
            $result = [];

            // Add precursor if we have one
            if ($precursor) {
                $result['precursor'] = [
                    'name' => self::titleCase($precursor['name']),
                    'url' => '/dex/' . $precursor['name']
                ];
            }

            // Add successor if there's an evolution
            $evolvesTo = $chain['evolves_to'] ?? [];
            if (!empty($evolvesTo) && isset($evolvesTo[0]['species']['name'])) {
                $successorName = $evolvesTo[0]['species']['name'];
                $result['successor'] = [
                    'name' => self::titleCase($successorName),
                    'url' => '/dex/' . $successorName
                ];
            }

            return $result;
        }

        // Recursively search in evolution branches
        $evolvesTo = $chain['evolves_to'] ?? [];
        foreach ($evolvesTo as $evolution) {
            $found = $this->findPokemonInChain($evolution, $target_pokemon, [
                'name' => $currentSpecies
            ]);
            if ($found) {
                return $found;
            }
        }

        return null;
    }

    //! @brief Title case helper (first letter uppercase, rest lowercase)
    private static function titleCase(string $name): string
    {
        if ($name === '') {
            return $name;
        }
        return mb_strtoupper(mb_substr($name, 0, 1)) . mb_strtolower(mb_substr($name, 1));
    }
}


