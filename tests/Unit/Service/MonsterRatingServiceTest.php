<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Service\MonsterRatingService;
use App\Repository\TestMonsterRatingRepository;
use App\Repository\MonsterRatingRepository;
use App\Type\Result;
use App\Type\MonsterIdentifier;

final class MonsterRatingServiceTest extends TestCase
{
    public function test_get_rating_success(): void
    {
        //! @section Arrange
        $repository = new TestMonsterRatingRepository();
        $repository->addRating('maushold', 'A', 'Cute family Pokemon!');

        $service = new MonsterRatingService($repository);

        //! @section Act
        $result = $service->getRating(MonsterIdentifier::fromString('maushold'));

        //! @section Assert
        $this->assertTrue($result->isSuccess());
        $rating = $result->getValue();
        $this->assertSame('maushold', $rating->speciesName);
        $this->assertSame('A', $rating->rating);
    }

    public function test_get_rating_with_form_name(): void
    {
        //! @section Arrange
        $repository = new TestMonsterRatingRepository();
        $repository->addRating('maushold', 'A', 'Cute family Pokemon!');
        $repository->addFormMapping('maushold-family-of-four', 'maushold');

        $service = new MonsterRatingService($repository);

        //! @section Act
        $result = $service->getRating(MonsterIdentifier::fromString('maushold-family-of-four'));

        //! @section Assert
        $this->assertTrue($result->isSuccess());
        $rating = $result->getValue();
        $this->assertSame('maushold', $rating->speciesName);
        $this->assertSame('A', $rating->rating);
    }

    public function test_get_rating_failure(): void
    {
        //! @section Arrange
        $repository = new TestMonsterRatingRepository();
        $service = new MonsterRatingService($repository);

        //! @section Act
        $result = $service->getRating(MonsterIdentifier::fromString('nonexistent'));

        //! @section Assert
        $this->assertTrue($result->isFailure());
    }

    public function test_has_rating(): void
    {
        //! @section Arrange
        $repository = new TestMonsterRatingRepository();
        $repository->addRating('maushold', 'A', 'Cute family Pokemon!');

        $service = new MonsterRatingService($repository);

        //! @section Assert
        $this->assertTrue($service->hasRating(MonsterIdentifier::fromString('maushold')));
        $this->assertFalse($service->hasRating(MonsterIdentifier::fromString('nonexistent')));
    }

    public function test_get_all_rating_names(): void
    {
        //! @section Arrange
        $repository = new TestMonsterRatingRepository();
        $repository->addRating('maushold', 'A', 'Cute family!');
        $repository->addRating('pikachu', 'A', 'Iconic!');

        $service = new MonsterRatingService($repository);

        //! @section Act
        $names = $service->getAllRatingNames();

        //! @section Assert
        $this->assertCount(2, $names);
        $this->assertContains('maushold', $names);
        $this->assertContains('pikachu', $names);
    }

    public function test_get_ratings_count(): void
    {
        //! @section Arrange
        $repository = new TestMonsterRatingRepository();
        $repository->addRating('maushold', 'A', 'Cute family!');
        $repository->addRating('pikachu', 'A', 'Iconic!');

        $service = new MonsterRatingService($repository);

        //! @section Act
        $count = $service->getRatingsCount();

        //! @section Assert
        $this->assertSame(2, $count);
    }

    public function test_get_all_ratings(): void
    {
        //! @section Arrange
        $repository = new TestMonsterRatingRepository();
        $repository->addRating('maushold', 'A', 'Cute family!');
        $repository->addRating('pikachu', 'S', 'Iconic!');

        $service = new MonsterRatingService($repository);

        //! @section Act
        $allRatings = $service->getAllRatings();

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

        $service = new MonsterRatingService($repository);

        //! @section Act
        $aTier = $service->getRatingsByTier('A');
        $bTier = $service->getRatingsByTier('B');

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

        $service = new MonsterRatingService($repository);

        //! @section Act
        $tiers = $service->getAllTiers();

        //! @section Assert
        $this->assertCount(3, $tiers);
        $this->assertContains('A', $tiers);
        $this->assertContains('B', $tiers);
        $this->assertContains('S', $tiers);
    }

    public function test_get_repository(): void
    {
        //! @section Arrange
        $repository = new TestMonsterRatingRepository();
        $service = new MonsterRatingService($repository);

        //! @section Act
        $retrievedRepository = $service->getRepository();

        //! @section Assert
        $this->assertSame($repository, $retrievedRepository);
    }

    public function test_default_repository_is_file_based(): void
    {
        //! @section Arrange
        $service = new MonsterRatingService();

        //! @section Act
        $repository = $service->getRepository();

        //! @section Assert
        $this->assertInstanceOf(\App\Repository\FileMonsterRatingRepository::class, $repository);
    }

    public function test_species_extraction_from_complex_forms(): void
    {
        //! @section Arrange
        $repository = new TestMonsterRatingRepository();
        $repository->addRating('maushold', 'A', 'Cute family!');
        $repository->addRating('deoxys', 'B', 'Multiple forms!');
        $repository->addRating('arceus', 'A', 'God Pokemon!');
        $repository->addRating('unown', 'C', 'Letter Pokemon!');

        $repository->addFormMapping('maushold-family-of-four', 'maushold');
        $repository->addFormMapping('deoxys-normal', 'deoxys');
        $repository->addFormMapping('arceus-fire', 'arceus');
        $repository->addFormMapping('unown-a', 'unown');

        $service = new MonsterRatingService($repository);

        //! @section Act
        $mausholdHasRating = $service->hasRating(MonsterIdentifier::fromString('maushold-family-of-four'));
        $deoxysHasRating = $service->hasRating(MonsterIdentifier::fromString('deoxys-normal'));
        $arceusHasRating = $service->hasRating(MonsterIdentifier::fromString('arceus-fire'));
        $unownHasRating = $service->hasRating(MonsterIdentifier::fromString('unown-a'));

        // All should return the same rating as their species
        $mausholdResult = $service->getRating(MonsterIdentifier::fromString('maushold-family-of-four'));
        $deoxysResult = $service->getRating(MonsterIdentifier::fromString('deoxys-normal'));

        //! @section Assert
        $this->assertTrue($mausholdHasRating);
        $this->assertTrue($deoxysHasRating);
        $this->assertTrue($arceusHasRating);
        $this->assertTrue($unownHasRating);

        $this->assertSame('A', $mausholdResult->getValue()->rating);
        $this->assertSame('B', $deoxysResult->getValue()->rating);
    }
}
