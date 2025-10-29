<?php

declare(strict_types=1);

use App\Service\CatalogConfigLoader;
use App\Service\PokemonCatalogService;
use App\Service\PokeApiHttpClient;

require __DIR__ . '/../vendor/autoload.php';

$configPath = $argv[1] ?? 'example_data_load.yaml';
$configKey = $argv[2] ?? 'pokemon-catalog';
$outputPath = $argv[3] ?? 'var/eligible_pokemon.txt';

$loader = new CatalogConfigLoader();
$service = new PokemonCatalogService();

$cfg = $loader->load($configPath, $configKey);
// Use HTTP client for species-list strategy
if ($cfg->strategy === 'species-list') {
    $service = new PokemonCatalogService(new PokeApiHttpClient());
}
$service->refreshFromConfig($cfg);

$dbPath = (string)($cfg->caching['sqlite_path'] ?? 'var/catalog.sqlite');
$eligible = $service->getEligibleSpecies($dbPath);

$lines = [];
foreach ($eligible as $species) {
    $name = (string)($species['name'] ?? '');
    $forms = isset($species['forms']) && is_array($species['forms'])
        ? array_map(fn($f) => (string)($f['name'] ?? ''), $species['forms'])
        : [];
    $line = $name;
    if (!empty($forms)) {
        $line .= ': ' . implode(', ', array_filter($forms));
    }
    $lines[] = $line;
}

$dir = dirname($outputPath);
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}
file_put_contents($outputPath, implode(PHP_EOL, $lines) . PHP_EOL);

fwrite(STDOUT, "Wrote eligible pokemon list to $outputPath\n");


