<?php

declare(strict_types=1);

use App\Service\CatalogConfigLoader;
use App\Service\PokemonCatalogService;
use App\Service\PokeApiHttpClient;

require __DIR__ . '/../vendor/autoload.php';

$path = $argv[1] ?? 'example_data_load.yaml';
$key = $argv[2] ?? 'pokemon-catalog';

$loader = new CatalogConfigLoader();
$config = $loader->load($path, $key);

// Use HTTP client only when species-list strategy
if ($config->strategy === 'species-list') {
    $service = new PokemonCatalogService(new PokeApiHttpClient());
} else {
    $service = new PokemonCatalogService();
}
$service->refreshFromConfig($config);

fwrite(STDOUT, "Catalog refreshed from $path ($key)\n");


