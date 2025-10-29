<?php

declare(strict_types=1);

namespace App\Service;

use App\Type\CatalogConfig;
use Symfony\Component\Yaml\Yaml;
use InvalidArgumentException;

//! @brief Loads catalog build configuration from YAML
final class CatalogConfigLoader
{
    //! @param key top-level key (default 'pokemon-catalog')
    public function load(string $path, string $key = 'pokemon-catalog'): CatalogConfig
    {
        $data = Yaml::parseFile($path);
        if (!is_array($data) || !isset($data[$key]) || !is_array($data[$key])) {
            throw new InvalidArgumentException("Catalog config '$key' not found in $path");
        }
        $cfg = $data[$key];

        $strategy = (string)($cfg['source']['strategy'] ?? 'explicit-only');
        $filters = is_array($cfg['filters'] ?? null) ? $cfg['filters'] : [];
        $formsPolicy = is_array($cfg['forms_policy'] ?? null) ? $cfg['forms_policy'] : [];
        $overrides = is_array($cfg['overrides'] ?? null) ? $cfg['overrides'] : [];
        $explicit = is_array($cfg['explicit_species'] ?? null) ? $cfg['explicit_species'] : [];
        $caching = is_array($cfg['caching'] ?? null) ? $cfg['caching'] : [];

        $this->validate($strategy, $filters, $formsPolicy, $overrides, $explicit, $caching);

        return new CatalogConfig($strategy, $filters, $formsPolicy, $overrides, $explicit, $caching);
    }

    private function validate(string $strategy, array $filters, array $formsPolicy, array $overrides, array $explicit, array $caching): void
    {
        if (!in_array($strategy, ['species-list', 'natdex-range', 'explicit-only'], true)) {
            throw new InvalidArgumentException("Unsupported strategy '$strategy'");
        }
        // Basic shape checks for overrides/explicit entries
        foreach ($overrides as $ov) {
            if (!isset($ov['species'])) {
                throw new InvalidArgumentException('Override entry requires species');
            }
        }
        foreach ($explicit as $ex) {
            if (!isset($ex['species'])) {
                throw new InvalidArgumentException('explicit_species entry requires species');
            }
        }
        if (isset($caching['sqlite_path']) && !is_string($caching['sqlite_path'])) {
            throw new InvalidArgumentException('caching.sqlite_path must be a string');
        }
    }
}


