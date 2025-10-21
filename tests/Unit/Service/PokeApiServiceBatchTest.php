<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Service\PokeApiService;
use App\Type\MonsterIdentifier;
use App\Type\Result;
use App\Type\MonsterData;
use App\Type\MonsterType;

final class PokeApiServiceBatchTest extends TestCase
{
    public function test_batch_fetch_performance_improvement(): void
    {
        //! @section Arrange
        $service = new PokeApiService();
        $identifiers = [
            MonsterIdentifier::fromString('pikachu'),
            MonsterIdentifier::fromString('eevee'),
            MonsterIdentifier::fromString('charmander'),
            MonsterIdentifier::fromString('squirtle'),
            MonsterIdentifier::fromString('bulbasaur'),
        ];

        //! @section Act
        $startTime = microtime(true);
        $results = $service->fetchMonstersBatch($identifiers, null, 300);
        $endTime = microtime(true);
        $batchTime = $endTime - $startTime;

        //! @section Assert
        $this->assertCount(5, $results);

        // All results should be successful
        foreach ($results as $identifier => $result) {
            $this->assertTrue($result->isSuccess(), "Failed to fetch $identifier");
            $this->assertInstanceOf(MonsterData::class, $result->getValue());
        }

        // Verify we got the expected Pokemon
        $this->assertArrayHasKey('pikachu', $results);
        $this->assertArrayHasKey('eevee', $results);
        $this->assertArrayHasKey('charmander', $results);
        $this->assertArrayHasKey('squirtle', $results);
        $this->assertArrayHasKey('bulbasaur', $results);

        // Performance assertion: batch should be reasonably fast
        // (This is more of a smoke test - in real usage, batch should be much faster than individual calls)
        $this->assertLessThan(10.0, $batchTime, 'Batch fetch took too long');

        // Verify the data is correct
        $pikachu = $results['pikachu']->getValue();
        $this->assertEquals('Pikachu', $pikachu->name);
        $this->assertEquals(25, $pikachu->id);
        $this->assertEquals(MonsterType::ELECTRIC, $pikachu->type1);
    }

    public function test_batch_fetch_handles_mixed_success_failure(): void
    {
        //! @section Arrange
        $service = new PokeApiService();
        $identifiers = [
            MonsterIdentifier::fromString('pikachu'), // Valid
            MonsterIdentifier::fromString('invalid-pokemon-12345'), // Invalid
            MonsterIdentifier::fromString('eevee'), // Valid
        ];

        //! @section Act
        $results = $service->fetchMonstersBatch($identifiers, null, 300);

        //! @section Assert
        $this->assertCount(3, $results);

        // Valid Pokemon should succeed
        $this->assertTrue($results['pikachu']->isSuccess());
        $this->assertTrue($results['eevee']->isSuccess());

        // Invalid Pokemon should fail
        $this->assertTrue($results['invalid-pokemon-12345']->isFailure());
    }

    public function test_batch_fetch_uses_cache_efficiently(): void
    {
        //! @section Arrange
        $service = new PokeApiService();
        $identifiers = [
            MonsterIdentifier::fromString('pikachu'),
            MonsterIdentifier::fromString('eevee'),
        ];

        // First call - should hit the API
        $startTime = microtime(true);
        $results1 = $service->fetchMonstersBatch($identifiers, null, 300);
        $firstCallTime = microtime(true) - $startTime;

        // Second call - should use cache
        $startTime = microtime(true);
        $results2 = $service->fetchMonstersBatch($identifiers, null, 300);
        $secondCallTime = microtime(true) - $startTime;

        //! @section Assert
        $this->assertCount(2, $results1);
        $this->assertCount(2, $results2);

        // Both calls should succeed
        foreach ($results1 as $result) {
            $this->assertTrue($result->isSuccess());
        }
        foreach ($results2 as $result) {
            $this->assertTrue($result->isSuccess());
        }

        // Second call should be faster (using cache)
        $this->assertLessThan($firstCallTime, $secondCallTime, 'Second call should be faster due to caching');
    }
}
