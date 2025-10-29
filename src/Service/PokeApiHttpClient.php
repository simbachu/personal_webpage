<?php

declare(strict_types=1);

namespace App\Service;

final class PokeApiHttpClient implements PokeApiClientInterface
{
    private function get(string $url): array
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: PHP',
                    'Accept: application/json',
                ],
                'timeout' => 15,
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);
        $json = @file_get_contents($url, false, $context);
        if ($json === false) {
            throw new \RuntimeException('HTTP fetch failed: ' . $url);
        }
        /** @var array<string,mixed> */
        $data = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        return $data;
    }

    public function listSpecies(int $offset, int $limit): array
    {
        $data = $this->get('https://pokeapi.co/api/v2/pokemon-species?offset=' . $offset . '&limit=' . $limit);
        $results = $data['results'] ?? [];
        return is_array($results) ? $results : [];
    }

    public function getSpecies(string $name): array
    {
        return $this->get('https://pokeapi.co/api/v2/pokemon-species/' . rawurlencode($name));
    }

    public function getPokemon(string $name): array
    {
        return $this->get('https://pokeapi.co/api/v2/pokemon/' . rawurlencode($name));
    }
}


