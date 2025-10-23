<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Repository\TestMonsterRatingRepository;
use App\Repository\MonsterRatingRepository;
use App\Type\Result;
use App\Type\MonsterIdentifier;

final class TestMonsterRatingRepositoryTest extends TestCase
{
    public function test_add_and_get_rating(): void
    {
        //! @section Arrange
        $repository = new TestMonsterRatingRepository();
        $repository->addRating('maushold', 'A', 'Cute family Pokemon!');

        //! @section Act
        $result = $repository->getRating('maushold');

        //! @section Assert
        $this->assertTrue($result->isSuccess());
        $rating = $result->getValue();
        $this->assertSame('maushold', $rating->speciesName);
        $this->assertSame('A', $rating->rating);
        $this->assertSame('Cute family Pokemon!', $rating->opinion);
    }

    public function test_has_rating(): void
    {
        //! @section Arrange
        $repository = new TestMonsterRatingRepository();
        $repository->addRating('maushold', 'A', 'Cute family Pokemon!');

        //! @section Assert
        $this->assertTrue($repository->hasRating('maushold'));
        $this->assertFalse($repository->hasRating('nonexistent'));
    }

    public function test_get_all_species_names(): void
    {
        //! @section Arrange
        $repository = new TestMonsterRatingRepository();
        $repository->addRating('maushold', 'A', 'Cute family!');
        $repository->addRating('pikachu', 'A', 'Iconic!');

        //! @section Act
        $speciesNames = $repository->getAllSpeciesNames();

        //! @section Assert
        $this->assertCount(2, $speciesNames);
        $this->assertContains('maushold', $speciesNames);
        $this->assertContains('pikachu', $speciesNames);
    }

    public function test_get_ratings_count(): void
    {
        //! @section Arrange
        $repository = new TestMonsterRatingRepository();
        $repository->addRating('maushold', 'A', 'Cute family!');
        $repository->addRating('pikachu', 'A', 'Iconic!');

        //! @section Act
        $count = $repository->getRatingsCount();

        //! @section Assert
        $this->assertSame(2, $count);
    }

    public function test_get_all_ratings(): void
    {
        //! @section Arrange
        $repository = new TestMonsterRatingRepository();
        $repository->addRating('maushold', 'A', 'Cute family!');
        $repository->addRating('pikachu', 'S', 'Iconic!');

        //! @section Act
        $allRatings = $repository->getAllRatings();

        //! @section Assert
        $this->assertCount(2, $allRatings);
        $this->assertArrayHasKey('maushold', $allRatings);
        $this->assertArrayHasKey('pikachu', $allRatings);
        $this->assertSame('A', $allRatings['maushold']->rating);
        $this->assertSame('S', $allRatings['pikachu']->rating);
    }

    public function test_get_ratings_by_tier(): void
    {
        //! @section Arrange
        $repository = new TestMonsterRatingRepository();
        $repository->addRating('maushold', 'A', 'Cute family!');
        $repository->addRating('pikachu', 'A', 'Iconic!');
        $repository->addRating('deoxys', 'B', 'Multiple forms!');

        //! @section Act
        $aTier = $repository->getRatingsByTier('A');
        $bTier = $repository->getRatingsByTier('B');

        //! @section Assert
        $this->assertCount(2, $aTier);
        $this->assertCount(1, $bTier);
        $this->assertArrayHasKey('maushold', $aTier);
        $this->assertArrayHasKey('deoxys', $bTier);
    }

    public function test_get_all_tiers(): void
    {
        //! @section Arrange
        $repository = new TestMonsterRatingRepository();
        $repository->addRating('maushold', 'A', 'Cute family!');
        $repository->addRating('pikachu', 'S', 'Iconic!');
        $repository->addRating('deoxys', 'B', 'Multiple forms!');

        //! @section Act
        $tiers = $repository->getAllTiers();

        //! @section Assert
        $this->assertCount(3, $tiers);
        $this->assertContains('A', $tiers);
        $this->assertContains('B', $tiers);
        $this->assertContains('S', $tiers);
        $this->assertSame(['A', 'B', 'S'], $tiers); // Should be sorted
    }

    public function test_form_to_species_mapping(): void
    {
        //! @section Arrange
        $repository = new TestMonsterRatingRepository();
        $repository->addFormMapping('maushold-family-of-four', 'maushold');
        $repository->addFormMapping('deoxys-normal', 'deoxys');

        //! @section Act
        $mausholdSpecies = $repository->extractSpeciesName(MonsterIdentifier::fromString('maushold-family-of-four'));
        $deoxysSpecies = $repository->extractSpeciesName(MonsterIdentifier::fromString('deoxys-normal'));
        $pikachuSpecies = $repository->extractSpeciesName(MonsterIdentifier::fromString('pikachu'));

        //! @section Assert
        $this->assertSame('maushold', $mausholdSpecies);
        $this->assertSame('deoxys', $deoxysSpecies);
        $this->assertSame('pikachu', $pikachuSpecies);
    }

    public function test_clear_method(): void
    {
        //! @section Arrange
        $repository = new TestMonsterRatingRepository();
        $repository->addRating('maushold', 'A', 'Cute family!');
        $repository->addFormMapping('maushold-family-of-four', 'maushold');

        //! @section Act
        $repository->clear();

        //! @section Assert
        $this->assertSame(0, $repository->getRatingsCount());
        $this->assertEmpty($repository->getAllSpeciesNames());
        $this->assertFalse($repository->hasRating('maushold'));
    }

    public function test_case_insensitive_tier_matching(): void
    {
        //! @section Arrange
        $repository = new TestMonsterRatingRepository();
        $repository->addRating('maushold', 'a', 'Cute family!');
        $repository->addRating('pikachu', 'A', 'Iconic!');

        //! @section Act
        $aTierLower = $repository->getRatingsByTier('a');
        $aTierUpper = $repository->getRatingsByTier('A');

        //! @section Assert
        $this->assertCount(2, $aTierLower);
        $this->assertCount(2, $aTierUpper);
        $this->assertArrayHasKey('maushold', $aTierLower);
        $this->assertArrayHasKey('pikachu', $aTierUpper);
    }
}
