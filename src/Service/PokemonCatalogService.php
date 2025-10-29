<?php

declare(strict_types=1);

namespace App\Service;

use App\Type\CatalogConfig;
use PDO;

//! @brief Builds and serves a cached Pokemon species catalog for tournaments
final class PokemonCatalogService
{
    public function __construct(private readonly ?PokeApiClientInterface $poke = null) {}
    public function refreshFromConfig(CatalogConfig $cfg): void
    {
        $dbPath = (string)($cfg->caching['sqlite_path'] ?? 'var/catalog.sqlite');
        $pdo = $this->tryOpenSqlite($dbPath);
        if ($pdo !== null) {
            $this->ensureSchema($pdo);
        }

        // species-list strategy using injected PokeAPI client
        if ($cfg->strategy === 'species-list') {
            if ($this->poke === null) {
                throw new \InvalidArgumentException('species-list strategy requires a PokeAPI client');
            }
            $now = time();
            $jsonFallback = [];
            $this->buildFromSpeciesList($cfg, $pdo, $jsonFallback, $now);
            if ($pdo === null) { $this->writeJsonFallback($dbPath, $jsonFallback); }
            return;
        }

        $overridesBySpecies = [];
        foreach ($cfg->overrides as $ov) {
            $overridesBySpecies[strtolower(trim((string)$ov['species']))] = $ov;
        }
        $speciesPolicy = [];
        if (isset($cfg->formsPolicy['species_overrides']) && is_array($cfg->formsPolicy['species_overrides'])) {
            foreach ($cfg->formsPolicy['species_overrides'] as $speciesName => $policy) {
                $speciesPolicy[strtolower(trim((string)$speciesName))] = is_array($policy) ? $policy : [];
            }
        }

        $now = time();
        $jsonFallback = [];
        $stmt = $pdo?->prepare('REPLACE INTO pokemon_species (species_name, display_name, image_url, forms_json, updated_at) VALUES (:name, :display, :image, :forms, :ts)');

        foreach ($cfg->explicitSpecies as $entry) {
            $speciesKey = strtolower(trim((string)$entry['species']));
            $override = $overridesBySpecies[$speciesKey] ?? [];
            $display = (string)($override['display_name'] ?? self::titleCase($speciesKey));
            $image = (string)($override['image'] ?? '');
            $forms = [];

            // forms from explicit entry
            if (isset($entry['forms']) && is_array($entry['forms'])) {
                foreach ($entry['forms'] as $formName) {
                    $forms[] = [
                        'name' => (string)$formName,
                        'display_name' => self::titleCase((string)$formName),
                        'image' => ''
                    ];
                }
            }

            // overrides may add/rename forms
            if (isset($override['forms']) && is_array($override['forms'])) {
                foreach ($override['forms'] as $ovForm) {
                    $forms[] = [
                        'name' => (string)($ovForm['name'] ?? ''),
                        'display_name' => (string)($ovForm['display_name'] ?? self::titleCase((string)($ovForm['name'] ?? ''))),
                        'image' => (string)($ovForm['image'] ?? '')
                    ];
                }
            }

            // apply forms policy per species
            $policy = $speciesPolicy[$speciesKey] ?? [];
            $mode = (string)($policy['mode'] ?? 'keep_forms');
            if ($mode === 'separate_species') {
                $forms = []; // do not include forms for this species
            } else {
                // normalize specific forms display names
                if (isset($policy['normalize_forms']) && is_array($policy['normalize_forms'])) {
                    if (empty($forms)) {
                        // seed forms from normalize list if none provided explicitly
                        foreach ($policy['normalize_forms'] as $norm) {
                            $from = (string)($norm['from'] ?? '');
                            $to = (string)($norm['to_display_name'] ?? self::titleCase($from));
                            if ($from !== '') {
                                $forms[] = [
                                    'name' => $from,
                                    'display_name' => $to,
                                    'image' => ''
                                ];
                            }
                        }
                    }
                    foreach ($policy['normalize_forms'] as $norm) {
                        $from = strtolower((string)($norm['from'] ?? ''));
                        $to = (string)($norm['to_display_name'] ?? '');
                        foreach ($forms as &$f) {
                            if (strtolower((string)($f['name'] ?? '')) === $from && $to !== '') {
                                $f['display_name'] = $to;
                            }
                        }
                        unset($f);
                    }
                }
            }

            if ($stmt) {
                $stmt->execute([
                    ':name' => $speciesKey,
                    ':display' => $display,
                    ':image' => $image,
                    ':forms' => json_encode($forms, JSON_UNESCAPED_SLASHES),
                    ':ts' => $now,
                ]);
            } else {
                $jsonFallback[] = [
                    'species_name' => $speciesKey,
                    'display_name' => $display,
                    'image_url' => $image,
                    'forms_json' => json_encode($forms, JSON_UNESCAPED_SLASHES),
                    'updated_at' => $now,
                ];
            }
        }

        if ($pdo === null) {
            $this->writeJsonFallback($dbPath, $jsonFallback);
        }
    }

    private function buildFromSpeciesList(CatalogConfig $cfg, ?PDO $pdo, array &$jsonFallback, int $timestamp): void
    {
        $overridesBySpecies = [];
        foreach ($cfg->overrides as $ov) {
            $overridesBySpecies[strtolower(trim((string)$ov['species']))] = $ov;
        }
        $speciesPolicy = [];
        if (isset($cfg->formsPolicy['species_overrides']) && is_array($cfg->formsPolicy['species_overrides'])) {
            foreach ($cfg->formsPolicy['species_overrides'] as $speciesName => $policy) {
                $speciesPolicy[strtolower(trim((string)$speciesName))] = is_array($policy) ? $policy : [];
            }
        }

        $includeLegendary = (bool)($cfg->filters['include_legendary'] ?? true);
        $includeMythical = (bool)($cfg->filters['include_mythical'] ?? true);

        $stmt = $pdo?->prepare('REPLACE INTO pokemon_species (species_name, display_name, image_url, forms_json, updated_at) VALUES (:name, :display, :image, :forms, :ts)');

        $offset = 0; $limit = 200;
        while (true) {
            $batch = $this->poke->listSpecies($offset, $limit);
            if (empty($batch)) { break; }
            foreach ($batch as $entry) {
                $speciesKey = strtolower((string)$entry['name']);
                $spec = $this->poke->getSpecies($speciesKey);
                $isLegendary = (bool)($spec['is_legendary'] ?? false);
                $isMythical = (bool)($spec['is_mythical'] ?? false);
                if ((!$includeLegendary && $isLegendary) || (!$includeMythical && $isMythical)) {
                    continue;
                }

                $override = $overridesBySpecies[$speciesKey] ?? [];
                $display = (string)($override['display_name'] ?? self::titleCase($speciesKey));
                $image = (string)($override['image'] ?? '');

                // Resolve a default image from the default variety if available
                if ($image === '') {
                    $defaultVariety = null;
                    foreach (($spec['varieties'] ?? []) as $var) {
                        if (!empty($var['is_default'])) { $defaultVariety = $var; break; }
                    }
                    $pokemonName = $defaultVariety['pokemon']['name'] ?? $speciesKey;
                    $pokeJson = $this->poke->getPokemon($pokemonName);
                    $image = (string)($pokeJson['sprites']['other']['official-artwork']['front_default']
                        ?? $pokeJson['sprites']['front_default'] ?? '');
                }

                // Build forms from varieties
                $forms = [];
                foreach (($spec['varieties'] ?? []) as $var) {
                    $varName = (string)($var['pokemon']['name'] ?? '');
                    if ($varName === '' || !empty($var['is_default'])) { continue; }
                    $forms[] = [
                        'name' => $varName,
                        'display_name' => self::titleCase($varName),
                        'image' => '',
                    ];
                }

                // apply forms policy per species
                $policy = $speciesPolicy[$speciesKey] ?? [];
                $mode = (string)($policy['mode'] ?? 'keep_forms');
                if ($mode === 'separate_species') {
                    $forms = [];
                } else {
                    if (isset($policy['normalize_forms']) && is_array($policy['normalize_forms'])) {
                        if (empty($forms)) {
                            foreach ($policy['normalize_forms'] as $norm) {
                                $from = (string)($norm['from'] ?? '');
                                $to = (string)($norm['to_display_name'] ?? self::titleCase($from));
                                if ($from !== '') {
                                    $forms[] = [ 'name' => $from, 'display_name' => $to, 'image' => '' ];
                                }
                            }
                        }
                        foreach ($policy['normalize_forms'] as $norm) {
                            $from = strtolower((string)($norm['from'] ?? ''));
                            $to = (string)($norm['to_display_name'] ?? '');
                            foreach ($forms as &$f) {
                                if (strtolower((string)($f['name'] ?? '')) === $from && $to !== '') {
                                    $f['display_name'] = $to;
                                }
                            }
                            unset($f);
                        }
                    }
                }

                if ($stmt) {
                    $stmt->execute([
                        ':name' => $speciesKey,
                        ':display' => $display,
                        ':image' => $image,
                        ':forms' => json_encode($forms, JSON_UNESCAPED_SLASHES),
                        ':ts' => $timestamp,
                    ]);
                } else {
                    $jsonFallback[] = [
                        'species_name' => $speciesKey,
                        'display_name' => $display,
                        'image_url' => $image,
                        'forms_json' => json_encode($forms, JSON_UNESCAPED_SLASHES),
                        'updated_at' => $timestamp,
                    ];
                }
            }
            if (count($batch) < $limit) { break; }
            $offset += $limit;
        }
    }

    //! @return array<int,array{name:string,image:string,forms:array<int,array{name:string,image:string}>}>
    public function getEligibleSpecies(string $dbPath): array
    {
        if (!file_exists($dbPath) && !file_exists($dbPath . '.json')) {
            return [];
        }
        $pdo = $this->tryOpenSqlite($dbPath);
        if ($pdo) {
            $rows = $pdo->query('SELECT species_name, display_name, image_url, forms_json FROM pokemon_species ORDER BY display_name ASC')->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $rows = $this->readJsonFallback($dbPath);
        }
        $out = [];
        foreach ($rows as $r) {
            $forms = json_decode((string)$r['forms_json'], true) ?: [];
            $out[] = [
                'name' => (string)$r['display_name'],
                'image' => (string)$r['image_url'],
                'forms' => array_map(fn($f) => ['name' => (string)($f['display_name'] ?? ''), 'image' => (string)($f['image'] ?? '')], $forms),
            ];
        }
        return $out;
    }

    private function tryOpenSqlite(string $dbPath): ?PDO
    {
        try {
            if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
                return null;
            }
            $dir = dirname($dbPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            $pdo = new PDO('sqlite:' . $dbPath);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function ensureSchema(PDO $pdo): void
    {
        $pdo->exec('CREATE TABLE IF NOT EXISTS pokemon_species (
            species_name TEXT PRIMARY KEY,
            display_name TEXT NOT NULL,
            image_url TEXT NOT NULL,
            forms_json TEXT NOT NULL,
            updated_at INTEGER NOT NULL
        )');
    }

    /** @param array<int,array<string,mixed>> $rows */
    private function writeJsonFallback(string $dbPath, array $rows): void
    {
        $dir = dirname($dbPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($dbPath . '.json', json_encode($rows, JSON_UNESCAPED_SLASHES));
    }

    /** @return array<int,array<string,mixed>> */
    private function readJsonFallback(string $dbPath): array
    {
        $file = $dbPath . '.json';
        if (!file_exists($file)) { return []; }
        $json = file_get_contents($file) ?: '[]';
        $rows = json_decode($json, true);
        return is_array($rows) ? $rows : [];
    }

    private static function titleCase(string $s): string
    {
        if ($s === '') { return $s; }
        return mb_strtoupper(mb_substr($s, 0, 1)) . mb_strtolower(mb_substr($s, 1));
    }
}


