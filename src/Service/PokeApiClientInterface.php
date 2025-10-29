<?php

declare(strict_types=1);

namespace App\Service;

//! @brief Minimal PokeAPI client contract for catalog builds
interface PokeApiClientInterface
{
    /**
     * @return array<int,array{name:string,url:string}>
     */
    public function listSpecies(int $offset, int $limit): array;

    /**
     * @return array<string,mixed> species JSON
     */
    public function getSpecies(string $name): array;

    /**
     * @return array<string,mixed> pokemon JSON
     */
    public function getPokemon(string $name): array;
}


