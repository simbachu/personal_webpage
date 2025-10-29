<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Service\CatalogConfigLoader;

final class CatalogConfigLoaderTest extends TestCase
{
    public function test_loads_example_data_load_yaml(): void
    {
        //! @section Arrange
        $loader = new CatalogConfigLoader();
        $path = __DIR__ . '/../../..' . '/example_data_load.yaml';

        //! @section Act
        $cfg = $loader->load($path);

        //! @section Assert
        $this->assertSame('explicit-only', $cfg->strategy);
        $this->assertIsArray($cfg->explicitSpecies);
        $this->assertGreaterThanOrEqual(1, count($cfg->explicitSpecies));
        $this->assertIsArray($cfg->overrides);
        $this->assertIsArray($cfg->formsPolicy);
        $this->assertIsArray($cfg->caching);
        $this->assertArrayHasKey('sqlite_path', $cfg->caching);
    }
}


