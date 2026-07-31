<?php

declare(strict_types=1);

namespace Tests\Unit\Dex;

use PHPUnit\Framework\TestCase;
use App\Dex\RatingData;

//! @brief RatingData parsing and tier predicates (not constructor echo)
final class RatingDataTest extends TestCase
{
    public function test_from_array(): void
    {
        //! @section Arrange
        $data = [
            'species_name' => 'maushold',
            'opinion' => 'Cute family Pokemon!',
            'rating' => 'A',
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
            'rating' => 'S',
        ];

        //! @section Act
        $rating = RatingData::fromArray($data);

        //! @section Assert
        $this->assertSame('pikachu', $rating->speciesName);
        $this->assertSame('Iconic Pokemon!', $rating->opinion);
        $this->assertSame('S', $rating->rating);
    }

    public function test_to_array_uses_snake_case_keys(): void
    {
        //! @section Arrange
        $rating = new RatingData('maushold', 'Cute family Pokemon!', 'A');

        //! @section Act
        $array = $rating->toArray();

        //! @section Assert
        $this->assertSame('maushold', $array['species_name']);
        $this->assertSame('Cute family Pokemon!', $array['opinion']);
        $this->assertSame('A', $array['rating']);
    }

    public function test_tier_predicates_and_case_insensitive_matching(): void
    {
        //! @section Arrange
        $sTier = new RatingData('mewtwo', 'Legendary', 's');
        $aTier = new RatingData('eevee', 'Cute', 'A');

        //! @section Assert
        $this->assertTrue($sTier->isSTier());
        $this->assertTrue($sTier->hasTier('S'));
        $this->assertTrue($sTier->hasTier('s'));
        $this->assertFalse($sTier->isATier());

        $this->assertTrue($aTier->isATier());
        $this->assertTrue($aTier->hasTier('a'));
        $this->assertFalse($aTier->isSTier());
    }
}
