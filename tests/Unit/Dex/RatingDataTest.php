<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Type\RatingData;

final class RatingDataTest extends TestCase
{
    public function test_constructor_and_properties(): void
    {
        //! @section Arrange
        $rating = new RatingData(
            speciesName: 'maushold',
            opinion: 'They are such a cute little family! So delightful!',
            rating: 'A'
        );

        //! @section Assert
        $this->assertSame('maushold', $rating->speciesName);
        $this->assertSame('They are such a cute little family! So delightful!', $rating->opinion);
        $this->assertSame('A', $rating->rating);
    }

    public function test_tier_methods(): void
    {
        //! @section Arrange
        $sTier = new RatingData('pikachu', 'Iconic electric mouse!', 'S');
        $aTier = new RatingData('eevee', 'Cute evolution Pokemon!', 'A');
        $bTier = new RatingData('pidgey', 'Basic bird Pokemon', 'B');
        $cTier = new RatingData('rattata', 'Common early game Pokemon', 'C');
        $dTier = new RatingData('magikarp', 'Famous for being useless', 'D');

        //! @section Assert
        $this->assertTrue($sTier->isSTier());
        $this->assertFalse($sTier->isATier());
        $this->assertTrue($sTier->hasTier('S'));
        $this->assertFalse($sTier->hasTier('A'));

        $this->assertFalse($aTier->isSTier());
        $this->assertTrue($aTier->isATier());
        $this->assertTrue($aTier->hasTier('A'));

        $this->assertTrue($bTier->isBTier());
        $this->assertTrue($cTier->isCTier());
        $this->assertTrue($dTier->isDTier());
    }

    public function test_to_array(): void
    {
        //! @section Arrange
        $rating = new RatingData(
            speciesName: 'maushold',
            opinion: 'Cute family Pokemon!',
            rating: 'A'
        );

        //! @section Act
        $array = $rating->toArray();

        //! @section Assert
        $this->assertIsArray($array);
        $this->assertSame('maushold', $array['species_name']);
        $this->assertSame('Cute family Pokemon!', $array['opinion']);
        $this->assertSame('A', $array['rating']);
    }

    public function test_from_array(): void
    {
        //! @section Arrange
        $data = [
            'species_name' => 'maushold',
            'opinion' => 'Cute family Pokemon!',
            'rating' => 'A'
        ];

        //! @section Act
        $rating = RatingData::fromArray($data);

        //! @section Assert
        $this->assertSame('maushold', $rating->speciesName);
        $this->assertSame('Cute family Pokemon!', $rating->opinion);
        $this->assertSame('A', $rating->rating);
    }

    public function test_from_array_with_alternative_keys(): void
    {
        //! @section Arrange
        $data = [
            'speciesName' => 'pikachu',
            'opinion' => 'Iconic Pokemon!',
            'rating' => 'S'
        ];

        //! @section Act
        $rating = RatingData::fromArray($data);

        //! @section Assert
        $this->assertSame('pikachu', $rating->speciesName);
        $this->assertSame('Iconic Pokemon!', $rating->opinion);
        $this->assertSame('S', $rating->rating);
    }

    public function test_tier_case_insensitive(): void
    {
        //! @section Arrange
        $lowerS = new RatingData('mewtwo', 'Legendary psychic Pokemon!', 's');
        $upperA = new RatingData('charizard', 'Fire flying starter!', 'A');

        //! @section Assert
        $this->assertTrue($lowerS->isSTier());
        $this->assertTrue($lowerS->hasTier('S'));
        $this->assertTrue($lowerS->hasTier('s'));

        $this->assertTrue($upperA->isATier());
        $this->assertTrue($upperA->hasTier('A'));
        $this->assertTrue($upperA->hasTier('a'));
    }
}
