<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Service\CatalogConfigLoader;
use App\Service\PokemonCatalogService;
use App\Service\PokeApiClientInterface;

final class PokemonCatalogSpeciesListTest extends TestCase
{
    public function test_species_list_build_with_policies(): void
    {
        //! @section Arrange
        $loader = new CatalogConfigLoader();
        $cfg = $loader->load(__DIR__ . '/../../..' . '/example_data_load.yaml');

        // Switch strategy to species-list for test
        $tmpDb = sys_get_temp_dir() . '/catalog_test_' . uniqid() . '.sqlite';
        $cfg = new \App\Type\CatalogConfig(
            'species-list',
            $cfg->filters,
            $cfg->formsPolicy,
            $cfg->overrides,
            $cfg->explicitSpecies,
            array_merge($cfg->caching, ['sqlite_path' => $tmpDb])
        );

        // Mock PokeAPI client
        $poke = $this->createMock(PokeApiClientInterface::class);

        // listSpecies returns a single page
        $poke->method('listSpecies')->willReturnOnConsecutiveCalls(
            [
                ['name' => 'eevee', 'url' => 'u1'],
                ['name' => 'vaporeon', 'url' => 'u2'],
                ['name' => 'mewtwo', 'url' => 'u3'],
                ['name' => 'maushold', 'url' => 'u4'],
            ],
            []
        );

        $poke->method('getSpecies')->willReturnMap([
            ['eevee', ['is_legendary' => false, 'is_mythical' => false, 'varieties' => [['is_default' => true, 'pokemon' => ['name' => 'eevee']]]]],
            ['vaporeon', ['is_legendary' => false, 'is_mythical' => false, 'varieties' => [['is_default' => true, 'pokemon' => ['name' => 'vaporeon']]]]],
            ['mewtwo', ['is_legendary' => true, 'is_mythical' => false, 'varieties' => [
                ['is_default' => true, 'pokemon' => ['name' => 'mewtwo']],
                ['is_default' => false, 'pokemon' => ['name' => 'mega-mewtwo-x']],
                ['is_default' => false, 'pokemon' => ['name' => 'mega-mewtwo-y']],
            ]]],
            ['maushold', ['is_legendary' => false, 'is_mythical' => false, 'varieties' => [
                ['is_default' => true, 'pokemon' => ['name' => 'maushold']],
                ['is_default' => false, 'pokemon' => ['name' => 'maushold-family-of-three']],
                ['is_default' => false, 'pokemon' => ['name' => 'maushold-family-of-four']],
            ]]],
        ]);

        $poke->method('getPokemon')->willReturnCallback(function (string $name) {
            return ['sprites' => ['other' => ['official-artwork' => ['front_default' => 'url-' . $name]], 'front_default' => 'url-' . $name]];
        });

        $svc = new PokemonCatalogService($poke);

        //! @section Act
        $svc->refreshFromConfig($cfg);
        $rows = $svc->getEligibleSpecies($tmpDb);

        //! @section Assert
        $names = array_map(fn($r) => $r['name'], $rows);
        $this->assertContains('Eevee', $names);
        $this->assertContains('Vaporeon', $names);
        $this->assertContains('Mewtwo', $names);
        $this->assertContains('Maushold', $names);

        // Eevee has no forms; Mewtwo and Maushold have forms
        $byName = [];
        foreach ($rows as $r) { $byName[$r['name']] = $r; }
        $this->assertCount(0, $byName['Eevee']['forms']);
        $this->assertGreaterThanOrEqual(2, count($byName['Mewtwo']['forms']));
        $formNames = array_map(fn($f) => $f['name'], $byName['Maushold']['forms']);
        $this->assertContains('Family of Four', $formNames);
        $this->assertContains('Family of Three (rare)', $formNames);
    }
}


