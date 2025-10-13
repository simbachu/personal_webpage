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
    //! @return array{id:int,name:string,image:string,type1:string,type2?:string}
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

        return $monster;
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


