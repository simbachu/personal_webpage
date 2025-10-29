<?php

declare(strict_types=1);

namespace App\Type;

//! @brief DTO for catalog build configuration
final class CatalogConfig
{
    public function __construct(
        public readonly string $strategy, // species-list | natdex-range | explicit-only
        public readonly array $filters,   // associative
        public readonly array $formsPolicy, // associative
        public readonly array $overrides, // list of override entries
        public readonly array $explicitSpecies, // list of species entries
        public readonly array $caching    // associative (ttl_days, sqlite_path, ...)
    ) {}
}


