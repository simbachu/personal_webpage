<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Repository\FileMonsterRatingRepository;
use App\Repository\MonsterRatingRepository;
use App\Type\Result;
use App\Type\MonsterIdentifier;

final class FileMonsterRatingRepositoryTest extends TestCase
{
    private const TEST_RATINGS_FILE = 'tests/TestData/pokemon_ratings_test.yaml';

    protected function setUp(): void
    {
        // Create test data file
        $yamlContent = <<<YAML
maushold:
  opinion: "They are such a cute little family! So delightful!"
  rating: A
deoxys:
  opinion: "The different forms are quite cool, but I think the concept is a bit overdone."
  rating: B
arceus:
  opinion: "The god Pokemon! Very cool concept and the different type forms are interesting."
  rating: A
pikachu:
  opinion: "My sister caught a wild Pikachu in Viridian Forest. Of course its the most iconic Pokémon ever!"
  rating: A
YAML;

        file_put_contents(self::TEST_RATINGS_FILE, $yamlContent);
    }

    protected function tearDown(): void
    {
        if (file_exists(self::TEST_RATINGS_FILE)) {
            unlink(self::TEST_RATINGS_FILE);
        }
    }

    public function test_get_rating_success(): void
    {
        //! @section Arrange
        $repository = new FileMonsterRatingRepository(self::TEST_RATINGS_FILE);

        //! @section Act
        $result = $repository->getRating('maushold');

        //! @section Assert
        $this->assertTrue($result->isSuccess());
        $rating = $result->getValue();
        $this->assertSame('maushold', $rating->speciesName);
        $this->assertSame('A', $rating->rating);
        $this->assertStringContainsString('cute little family', $rating->opinion);
    }

    public function test_get_rating_failure(): void
    {
        //! @section Arrange
        $repository = new FileMonsterRatingRepository(self::TEST_RATINGS_FILE);

        //! @section Act
        $result = $repository->getRating('nonexistent');

        //! @section Assert
        $this->assertTrue($result->isFailure());
        $this->assertStringContainsString('No rating found', $result->getError());
    }

    public function test_has_rating(): void
    {
        //! @section Arrange
        $repository = new FileMonsterRatingRepository(self::TEST_RATINGS_FILE);

        //! @section Assert
        $this->assertTrue($repository->hasRating('maushold'));
        $this->assertTrue($repository->hasRating('pikachu'));
        $this->assertFalse($repository->hasRating('nonexistent'));
    }

    public function test_get_all_species_names(): void
    {
        //! @section Arrange
        $repository = new FileMonsterRatingRepository(self::TEST_RATINGS_FILE);

        //! @section Act
        $speciesNames = $repository->getAllSpeciesNames();

        //! @section Assert
        $this->assertIsArray($speciesNames);
        $this->assertCount(4, $speciesNames);
        $this->assertContains('maushold', $speciesNames);
        $this->assertContains('deoxys', $speciesNames);
        $this->assertContains('arceus', $speciesNames);
        $this->assertContains('pikachu', $speciesNames);
    }

    public function test_get_ratings_count(): void
    {
        //! @section Arrange
        $repository = new FileMonsterRatingRepository(self::TEST_RATINGS_FILE);

        //! @section Act
        $count = $repository->getRatingsCount();

        //! @section Assert
        $this->assertSame(4, $count);
    }

    public function test_get_all_ratings(): void
    {
        //! @section Arrange
        $repository = new FileMonsterRatingRepository(self::TEST_RATINGS_FILE);

        //! @section Act
        $allRatings = $repository->getAllRatings();

        //! @section Assert
        $this->assertIsArray($allRatings);
        $this->assertCount(4, $allRatings);
        $this->assertArrayHasKey('maushold', $allRatings);
        $this->assertArrayHasKey('deoxys', $allRatings);
        $this->assertSame('A', $allRatings['maushold']->rating);
        $this->assertSame('B', $allRatings['deoxys']->rating);
    }

    public function test_get_ratings_by_tier(): void
    {
        //! @section Arrange
        $repository = new FileMonsterRatingRepository(self::TEST_RATINGS_FILE);

        //! @section Act
        $aTier = $repository->getRatingsByTier('A');
        $bTier = $repository->getRatingsByTier('B');

        //! @section Assert
        $this->assertCount(3, $aTier); // maushold, arceus, pikachu
        $this->assertCount(1, $bTier); // deoxys
        $this->assertArrayHasKey('maushold', $aTier);
        $this->assertArrayHasKey('deoxys', $bTier);
        $this->assertSame('A', $aTier['maushold']->rating);
        $this->assertSame('B', $bTier['deoxys']->rating);
    }

    public function test_get_all_tiers(): void
    {
        //! @section Arrange
        $repository = new FileMonsterRatingRepository(self::TEST_RATINGS_FILE);

        //! @section Act
        $tiers = $repository->getAllTiers();

        //! @section Assert
        $this->assertIsArray($tiers);
        $this->assertCount(2, $tiers); // A and B
        $this->assertContains('A', $tiers);
        $this->assertContains('B', $tiers);
        $this->assertSame(['A', 'B'], $tiers); // Should be sorted
    }

    public function test_extract_species_name_from_identifier(): void
    {
        //! @section Arrange
        $repository = new FileMonsterRatingRepository();

        //! @section Act
        $maushold1 = $repository->extractSpeciesName(MonsterIdentifier::fromString('maushold-family-of-four'));
        $maushold2 = $repository->extractSpeciesName(MonsterIdentifier::fromString('maushold-family-of-three'));
        $deoxys = $repository->extractSpeciesName(MonsterIdentifier::fromString('deoxys-normal'));
        $arceus = $repository->extractSpeciesName(MonsterIdentifier::fromString('arceus-fire'));
        $pikachu = $repository->extractSpeciesName(MonsterIdentifier::fromString('pikachu'));

        //! @section Assert
        $this->assertSame('maushold', $maushold1);
        $this->assertSame('maushold', $maushold2);
        $this->assertSame('deoxys', $deoxys);
        $this->assertSame('arceus', $arceus);
        $this->assertSame('pikachu', $pikachu);
    }

    public function test_nonexistent_file_returns_failure(): void
    {
        //! @section Arrange
        $repository = new FileMonsterRatingRepository('nonexistent_file.yaml');

        //! @section Act
        $result = $repository->getRating('maushold');

        //! @section Assert
        $this->assertTrue($result->isFailure());
        $this->assertStringContainsString('Failed to load ratings data', $result->getError());
    }
}
