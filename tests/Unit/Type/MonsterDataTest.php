<?php

declare(strict_types=1);

namespace Tests\Unit\Type;

use PHPUnit\Framework\TestCase;
use App\Type\MonsterData;
use App\Type\EvolutionData;

final class MonsterDataTest extends TestCase
{
    //! @brief Test creating MonsterData with single type
    public function test_creates_monster_data_with_single_type(): void
    {
        //! @section Arrange & Act
        $monster = new MonsterData(
            id: 25,
            name: 'Pikachu',
            image: 'https://example.com/pikachu.png',
            type1: 'electric'
        );

        //! @section Assert
        $this->assertSame(25, $monster->id);
        $this->assertSame('Pikachu', $monster->name);
        $this->assertSame('https://example.com/pikachu.png', $monster->image);
        $this->assertSame('electric', $monster->type1);
        $this->assertNull($monster->type2);
        $this->assertFalse($monster->isDualType());
        $this->assertSame(['electric'], $monster->getTypes());
    }

    //! @brief Test creating MonsterData with dual types
    public function test_creates_monster_data_with_dual_types(): void
    {
        //! @section Arrange & Act
        $monster = new MonsterData(
            id: 1,
            name: 'Bulbasaur',
            image: 'https://example.com/bulbasaur.png',
            type1: 'grass',
            type2: 'poison'
        );

        //! @section Assert
        $this->assertSame('grass', $monster->type1);
        $this->assertSame('poison', $monster->type2);
        $this->assertTrue($monster->isDualType());
        $this->assertSame(['grass', 'poison'], $monster->getTypes());
    }

    //! @brief Test creating MonsterData with evolution data
    public function test_creates_monster_data_with_evolution_data(): void
    {
        //! @section Arrange
        $precursor = new EvolutionData('Pichu', '/dex/pichu');
        $successors = [
            new EvolutionData('Raichu', '/dex/raichu')
        ];

        //! @section Act
        $monster = new MonsterData(
            id: 25,
            name: 'Pikachu',
            image: 'https://example.com/pikachu.png',
            type1: 'electric',
            precursor: $precursor,
            successors: $successors
        );

        //! @section Assert
        $this->assertSame($precursor, $monster->precursor);
        $this->assertSame($successors, $monster->successors);
        $this->assertTrue($monster->hasEvolutionData());
    }

    //! @brief Test hasEvolutionData returns false when no evolution data
    public function test_has_evolution_data_returns_false_when_no_evolution_data(): void
    {
        //! @section Arrange & Act
        $monster = new MonsterData(
            id: 25,
            name: 'Pikachu',
            image: 'https://example.com/pikachu.png',
            type1: 'electric'
        );

        //! @section Assert
        $this->assertFalse($monster->hasEvolutionData());
    }

    //! @brief Test toArray converts to template-compatible array
    public function test_to_array_converts_to_template_compatible_array(): void
    {
        //! @section Arrange
        $precursor = new EvolutionData('Pichu', '/dex/pichu');
        $successors = [
            new EvolutionData('Raichu', '/dex/raichu')
        ];

        $monster = new MonsterData(
            id: 25,
            name: 'Pikachu',
            image: 'https://example.com/pikachu.png',
            type1: 'electric',
            type2: null,
            precursor: $precursor,
            successors: $successors
        );

        //! @section Act
        $array = $monster->toArray();

        //! @section Assert
        $this->assertSame(25, $array['id']);
        $this->assertSame('Pikachu', $array['name']);
        $this->assertSame('https://example.com/pikachu.png', $array['image']);
        $this->assertSame('electric', $array['type1']);
        $this->assertArrayNotHasKey('type2', $array);
        $this->assertArrayHasKey('precursor', $array);
        $this->assertArrayHasKey('successor', $array); // Single successor becomes 'successor'
        $this->assertSame('Pichu', $array['precursor']['name']);
        $this->assertSame('/dex/pichu', $array['precursor']['url']);
        $this->assertSame('Raichu', $array['successor']['name']);
        $this->assertSame('/dex/raichu', $array['successor']['url']);
    }

    //! @brief Test toArray handles multiple successors
    public function test_to_array_handles_multiple_successors(): void
    {
        //! @section Arrange
        $successors = [
            new EvolutionData('Vaporeon', '/dex/vaporeon'),
            new EvolutionData('Jolteon', '/dex/jolteon'),
            new EvolutionData('Flareon', '/dex/flareon')
        ];

        $monster = new MonsterData(
            id: 133,
            name: 'Eevee',
            image: 'https://example.com/eevee.png',
            type1: 'normal',
            successors: $successors
        );

        //! @section Act
        $array = $monster->toArray();

        //! @section Assert
        $this->assertArrayHasKey('successors', $array);
        $this->assertCount(3, $array['successors']);
        $this->assertSame('Vaporeon', $array['successors'][0]['name']);
        $this->assertSame('Jolteon', $array['successors'][1]['name']);
        $this->assertSame('Flareon', $array['successors'][2]['name']);
    }

    //! @brief Test fromArray creates MonsterData from array
    public function test_from_array_creates_monster_data_from_array(): void
    {
        //! @section Arrange
        $array = [
            'id' => 25,
            'name' => 'Pikachu',
            'image' => 'https://example.com/pikachu.png',
            'type1' => 'electric',
            'type2' => null,
            'precursor' => [
                'name' => 'Pichu',
                'url' => '/dex/pichu'
            ],
            'successor' => [
                'name' => 'Raichu',
                'url' => '/dex/raichu'
            ]
        ];

        //! @section Act
        $monster = MonsterData::fromArray($array);

        //! @section Assert
        $this->assertSame(25, $monster->id);
        $this->assertSame('Pikachu', $monster->name);
        $this->assertSame('https://example.com/pikachu.png', $monster->image);
        $this->assertSame('electric', $monster->type1);
        $this->assertNull($monster->type2);
        $this->assertNotNull($monster->precursor);
        $this->assertSame('Pichu', $monster->precursor->name);
        $this->assertCount(1, $monster->successors);
        $this->assertSame('Raichu', $monster->successors[0]->name);
    }

    //! @brief Test fromArray handles multiple successors
    public function test_from_array_handles_multiple_successors(): void
    {
        //! @section Arrange
        $array = [
            'id' => 133,
            'name' => 'Eevee',
            'image' => 'https://example.com/eevee.png',
            'type1' => 'normal',
            'successors' => [
                ['name' => 'Vaporeon', 'url' => '/dex/vaporeon'],
                ['name' => 'Jolteon', 'url' => '/dex/jolteon']
            ]
        ];

        //! @section Act
        $monster = MonsterData::fromArray($array);

        //! @section Assert
        $this->assertCount(2, $monster->successors);
        $this->assertSame('Vaporeon', $monster->successors[0]->name);
        $this->assertSame('Jolteon', $monster->successors[1]->name);
    }

    //! @brief Test fromArray handles missing optional fields
    public function test_from_array_handles_missing_optional_fields(): void
    {
        //! @section Arrange
        $array = [
            'id' => 25,
            'name' => 'Pikachu',
            'image' => 'https://example.com/pikachu.png',
            'type1' => 'electric'
        ];

        //! @section Act
        $monster = MonsterData::fromArray($array);

        //! @section Assert
        $this->assertSame(25, $monster->id);
        $this->assertSame('Pikachu', $monster->name);
        $this->assertSame('electric', $monster->type1);
        $this->assertNull($monster->type2);
        $this->assertNull($monster->precursor);
        $this->assertEmpty($monster->successors);
    }

    //! @brief Test fromArray handles type2 field
    public function test_from_array_handles_type2_field(): void
    {
        //! @section Arrange
        $array = [
            'id' => 1,
            'name' => 'Bulbasaur',
            'image' => 'https://example.com/bulbasaur.png',
            'type1' => 'grass',
            'type2' => 'poison'
        ];

        //! @section Act
        $monster = MonsterData::fromArray($array);

        //! @section Assert
        $this->assertSame('grass', $monster->type1);
        $this->assertSame('poison', $monster->type2);
        $this->assertTrue($monster->isDualType());
    }

    //! @brief Test round-trip conversion (fromArray -> toArray)
    public function test_round_trip_conversion(): void
    {
        //! @section Arrange
        $originalArray = [
            'id' => 25,
            'name' => 'Pikachu',
            'image' => 'https://example.com/pikachu.png',
            'type1' => 'electric',
            'type2' => null,
            'precursor' => [
                'name' => 'Pichu',
                'url' => '/dex/pichu'
            ],
            'successor' => [
                'name' => 'Raichu',
                'url' => '/dex/raichu'
            ]
        ];

        //! @section Act
        $monster = MonsterData::fromArray($originalArray);
        $convertedArray = $monster->toArray();

        //! @section Assert
        $this->assertSame($originalArray['id'], $convertedArray['id']);
        $this->assertSame($originalArray['name'], $convertedArray['name']);
        $this->assertSame($originalArray['image'], $convertedArray['image']);
        $this->assertSame($originalArray['type1'], $convertedArray['type1']);
        $this->assertArrayNotHasKey('type2', $convertedArray);
        $this->assertSame($originalArray['precursor']['name'], $convertedArray['precursor']['name']);
        $this->assertSame($originalArray['successor']['name'], $convertedArray['successor']['name']);
    }
}
