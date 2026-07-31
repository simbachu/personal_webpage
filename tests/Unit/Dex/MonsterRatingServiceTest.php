<?php

declare(strict_types=1);

namespace Tests\Unit\Dex;

use PHPUnit\Framework\TestCase;
use App\Dex\MonsterRatingService;
use App\Dex\TestMonsterRatingRepository;
use App\Dex\MonsterIdentifier;
use App\Dex\FileMonsterRatingRepository;

//! @brief Service wiring: species extraction and default repository (not passthrough mirrors)
final class MonsterRatingServiceTest extends TestCase
{
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

    public function test_default_repository_is_file_based(): void
    {
        //! @section Arrange
        $service = new MonsterRatingService();

        //! @section Act
        $repository = $service->getRepository();

        //! @section Assert
        $this->assertInstanceOf(FileMonsterRatingRepository::class, $repository);
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
        $mausholdResult = $service->getRating(MonsterIdentifier::fromString('maushold-family-of-four'));
        $deoxysResult = $service->getRating(MonsterIdentifier::fromString('deoxys-normal'));

        //! @section Assert
        $this->assertTrue($service->hasRating(MonsterIdentifier::fromString('maushold-family-of-four')));
        $this->assertTrue($service->hasRating(MonsterIdentifier::fromString('deoxys-normal')));
        $this->assertTrue($service->hasRating(MonsterIdentifier::fromString('arceus-fire')));
        $this->assertTrue($service->hasRating(MonsterIdentifier::fromString('unown-a')));
        $this->assertSame('A', $mausholdResult->getValue()->rating);
        $this->assertSame('B', $deoxysResult->getValue()->rating);
    }
}
