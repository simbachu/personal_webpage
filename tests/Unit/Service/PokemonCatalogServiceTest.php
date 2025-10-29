<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Service\CatalogConfigLoader;
use App\Service\PokemonCatalogService;

final class PokemonCatalogServiceTest extends TestCase
{
    public function test_refresh_and_read_explicit_only(): void
    {
        //! @section Arrange
        $loader = new CatalogConfigLoader();
        $svc = new PokemonCatalogService();
        $cfg = $loader->load(__DIR__ . '/../../..' . '/example_data_load.yaml');

        // point to a temp sqlite path
        $tmpDb = sys_get_temp_dir() . '/catalog_test_' . uniqid() . '.sqlite';
        $cfg = new \App\Type\CatalogConfig(
            $cfg->strategy,
            $cfg->filters,
            $cfg->formsPolicy,
            $cfg->overrides,
            $cfg->explicitSpecies,
            array_merge($cfg->caching, ['sqlite_path' => $tmpDb])
        );

        //! @section Act
        $svc->refreshFromConfig($cfg);
        $rows = $svc->getEligibleSpecies($tmpDb);

        //! @section Assert
        $this->assertNotEmpty($rows);
        $names = array_map(fn($r) => $r['name'], $rows);
        $this->assertContains('Mewtwo', $names);
        $this->assertContains('Eevee', $names);
        $this->assertContains('Maushold', $names);
        // ensure forms array exists for mewtwo
        $mewtwo = null;
        foreach ($rows as $r) {
            if ($r['name'] === 'Mewtwo') { $mewtwo = $r; break; }
        }
        $this->assertIsArray($mewtwo['forms']);
        $this->assertGreaterThanOrEqual(1, count($mewtwo['forms']));
        // Eevee should not have forms (separate species policy)
        $eevee = null;
        foreach ($rows as $r) {
            if ($r['name'] === 'Eevee') { $eevee = $r; break; }
        }
        $this->assertIsArray($eevee['forms']);
        $this->assertCount(0, $eevee['forms']);
        // Maushold should have normalized form names
        $maushold = null;
        foreach ($rows as $r) {
            if ($r['name'] === 'Maushold') { $maushold = $r; break; }
        }
        $formNames = array_map(fn($f) => $f['name'], $maushold['forms']);
        $this->assertContains('Family of Four', $formNames);
        $this->assertContains('Family of Three (rare)', $formNames);
    }
}


