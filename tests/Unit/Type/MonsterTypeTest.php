<?php

declare(strict_types=1);

namespace Tests\Unit\Type;

use PHPUnit\Framework\TestCase;
use App\Type\MonsterType;

//! @brief Test suite for the MonsterType enum
class MonsterTypeTest extends TestCase
{
    public function test_all_monster_types_are_defined(): void
    {
        //! @section Act
        $types = MonsterType::cases();

        //! @section Assert
        $this->assertCount(18, $types);

        $expectedTypes = [
            'normal', 'fire', 'water', 'electric', 'grass', 'ice',
            'fighting', 'poison', 'ground', 'flying', 'psychic', 'bug',
            'rock', 'ghost', 'dragon', 'dark', 'steel', 'fairy'
        ];
        $actualTypes = array_column($types, 'value');

        foreach ($expectedTypes as $expected) {
            $this->assertContains($expected, $actualTypes, "Monster type '{$expected}' should be defined");
        }
    }

    public function test_monster_type_values_are_correct(): void
    {
        //! @section Act & Assert
        $this->assertSame('normal', MonsterType::NORMAL->value);
        $this->assertSame('fire', MonsterType::FIRE->value);
        $this->assertSame('water', MonsterType::WATER->value);
        $this->assertSame('electric', MonsterType::ELECTRIC->value);
        $this->assertSame('grass', MonsterType::GRASS->value);
        $this->assertSame('ice', MonsterType::ICE->value);
        $this->assertSame('fighting', MonsterType::FIGHTING->value);
        $this->assertSame('poison', MonsterType::POISON->value);
        $this->assertSame('ground', MonsterType::GROUND->value);
        $this->assertSame('flying', MonsterType::FLYING->value);
        $this->assertSame('psychic', MonsterType::PSYCHIC->value);
        $this->assertSame('bug', MonsterType::BUG->value);
        $this->assertSame('rock', MonsterType::ROCK->value);
        $this->assertSame('ghost', MonsterType::GHOST->value);
        $this->assertSame('dragon', MonsterType::DRAGON->value);
        $this->assertSame('dark', MonsterType::DARK->value);
        $this->assertSame('steel', MonsterType::STEEL->value);
        $this->assertSame('fairy', MonsterType::FAIRY->value);
    }

    public function test_from_string_with_valid_types(): void
    {
        //! @section Act & Assert
        $this->assertSame(MonsterType::NORMAL, MonsterType::fromString('normal'));
        $this->assertSame(MonsterType::FIRE, MonsterType::fromString('fire'));
        $this->assertSame(MonsterType::WATER, MonsterType::fromString('water'));
        $this->assertSame(MonsterType::ELECTRIC, MonsterType::fromString('electric'));
        $this->assertSame(MonsterType::GRASS, MonsterType::fromString('grass'));
        $this->assertSame(MonsterType::ICE, MonsterType::fromString('ice'));
        $this->assertSame(MonsterType::FIGHTING, MonsterType::fromString('fighting'));
        $this->assertSame(MonsterType::POISON, MonsterType::fromString('poison'));
        $this->assertSame(MonsterType::GROUND, MonsterType::fromString('ground'));
        $this->assertSame(MonsterType::FLYING, MonsterType::fromString('flying'));
        $this->assertSame(MonsterType::PSYCHIC, MonsterType::fromString('psychic'));
        $this->assertSame(MonsterType::BUG, MonsterType::fromString('bug'));
        $this->assertSame(MonsterType::ROCK, MonsterType::fromString('rock'));
        $this->assertSame(MonsterType::GHOST, MonsterType::fromString('ghost'));
        $this->assertSame(MonsterType::DRAGON, MonsterType::fromString('dragon'));
        $this->assertSame(MonsterType::DARK, MonsterType::fromString('dark'));
        $this->assertSame(MonsterType::STEEL, MonsterType::fromString('steel'));
        $this->assertSame(MonsterType::FAIRY, MonsterType::fromString('fairy'));
    }

    public function test_from_string_with_invalid_type(): void
    {
        //! @section Arrange
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid monster type: \'invalid\'. Valid types are: normal, fire, water, electric, grass, ice, fighting, poison, ground, flying, psychic, bug, rock, ghost, dragon, dark, steel, fairy');

        //! @section Act
        MonsterType::fromString('invalid');
    }

    public function test_from_string_with_empty_string(): void
    {
        //! @section Arrange
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid monster type: \'\'. Valid types are:');

        //! @section Act
        MonsterType::fromString('');
    }

    public function test_is_valid_with_valid_types(): void
    {
        //! @section Act & Assert
        $this->assertTrue(MonsterType::isValid('normal'));
        $this->assertTrue(MonsterType::isValid('fire'));
        $this->assertTrue(MonsterType::isValid('water'));
        $this->assertTrue(MonsterType::isValid('electric'));
        $this->assertTrue(MonsterType::isValid('grass'));
        $this->assertTrue(MonsterType::isValid('ice'));
        $this->assertTrue(MonsterType::isValid('fighting'));
        $this->assertTrue(MonsterType::isValid('poison'));
        $this->assertTrue(MonsterType::isValid('ground'));
        $this->assertTrue(MonsterType::isValid('flying'));
        $this->assertTrue(MonsterType::isValid('psychic'));
        $this->assertTrue(MonsterType::isValid('bug'));
        $this->assertTrue(MonsterType::isValid('rock'));
        $this->assertTrue(MonsterType::isValid('ghost'));
        $this->assertTrue(MonsterType::isValid('dragon'));
        $this->assertTrue(MonsterType::isValid('dark'));
        $this->assertTrue(MonsterType::isValid('steel'));
        $this->assertTrue(MonsterType::isValid('fairy'));
    }

    public function test_is_valid_with_invalid_types(): void
    {
        //! @section Act & Assert
        $this->assertFalse(MonsterType::isValid('invalid'));
        $this->assertFalse(MonsterType::isValid(''));
        $this->assertFalse(MonsterType::isValid('electic'));
        $this->assertFalse(MonsterType::isValid('electric '));
        $this->assertFalse(MonsterType::isValid(' ELECTRIC'));
        $this->assertFalse(MonsterType::isValid('Electric'));
    }

    public function test_get_all_values(): void
    {
        //! @section Act
        $allValues = MonsterType::getAllValues();

        //! @section Assert
        $this->assertIsArray($allValues);
        $this->assertCount(18, $allValues);
        $this->assertContains('normal', $allValues);
        $this->assertContains('fire', $allValues);
        $this->assertContains('water', $allValues);
        $this->assertContains('electric', $allValues);
        $this->assertContains('grass', $allValues);
        $this->assertContains('ice', $allValues);
        $this->assertContains('fighting', $allValues);
        $this->assertContains('poison', $allValues);
        $this->assertContains('ground', $allValues);
        $this->assertContains('flying', $allValues);
        $this->assertContains('psychic', $allValues);
        $this->assertContains('bug', $allValues);
        $this->assertContains('rock', $allValues);
        $this->assertContains('ghost', $allValues);
        $this->assertContains('dragon', $allValues);
        $this->assertContains('dark', $allValues);
        $this->assertContains('steel', $allValues);
        $this->assertContains('fairy', $allValues);
    }

    public function test_get_display_name(): void
    {
        //! @section Act & Assert
        $this->assertSame('Normal', MonsterType::NORMAL->getDisplayName());
        $this->assertSame('Fire', MonsterType::FIRE->getDisplayName());
        $this->assertSame('Water', MonsterType::WATER->getDisplayName());
        $this->assertSame('Electric', MonsterType::ELECTRIC->getDisplayName());
        $this->assertSame('Grass', MonsterType::GRASS->getDisplayName());
        $this->assertSame('Ice', MonsterType::ICE->getDisplayName());
        $this->assertSame('Fighting', MonsterType::FIGHTING->getDisplayName());
        $this->assertSame('Poison', MonsterType::POISON->getDisplayName());
        $this->assertSame('Ground', MonsterType::GROUND->getDisplayName());
        $this->assertSame('Flying', MonsterType::FLYING->getDisplayName());
        $this->assertSame('Psychic', MonsterType::PSYCHIC->getDisplayName());
        $this->assertSame('Bug', MonsterType::BUG->getDisplayName());
        $this->assertSame('Rock', MonsterType::ROCK->getDisplayName());
        $this->assertSame('Ghost', MonsterType::GHOST->getDisplayName());
        $this->assertSame('Dragon', MonsterType::DRAGON->getDisplayName());
        $this->assertSame('Dark', MonsterType::DARK->getDisplayName());
        $this->assertSame('Steel', MonsterType::STEEL->getDisplayName());
        $this->assertSame('Fairy', MonsterType::FAIRY->getDisplayName());
    }

    public function test_is_physical_type(): void
    {
        //! @section Act & Assert
        // Physical types
        $this->assertTrue(MonsterType::NORMAL->isPhysicalType());
        $this->assertTrue(MonsterType::FIGHTING->isPhysicalType());
        $this->assertTrue(MonsterType::POISON->isPhysicalType());
        $this->assertTrue(MonsterType::GROUND->isPhysicalType());
        $this->assertTrue(MonsterType::FLYING->isPhysicalType());
        $this->assertTrue(MonsterType::BUG->isPhysicalType());
        $this->assertTrue(MonsterType::ROCK->isPhysicalType());
        $this->assertTrue(MonsterType::GHOST->isPhysicalType());
        $this->assertTrue(MonsterType::DRAGON->isPhysicalType());
        $this->assertTrue(MonsterType::DARK->isPhysicalType());
        $this->assertTrue(MonsterType::STEEL->isPhysicalType());
        $this->assertTrue(MonsterType::FAIRY->isPhysicalType());

        // Special types
        $this->assertFalse(MonsterType::FIRE->isPhysicalType());
        $this->assertFalse(MonsterType::WATER->isPhysicalType());
        $this->assertFalse(MonsterType::ELECTRIC->isPhysicalType());
        $this->assertFalse(MonsterType::GRASS->isPhysicalType());
        $this->assertFalse(MonsterType::ICE->isPhysicalType());
        $this->assertFalse(MonsterType::PSYCHIC->isPhysicalType());
    }

    public function test_is_special_type(): void
    {
        //! @section Act & Assert
        // Special types
        $this->assertTrue(MonsterType::FIRE->isSpecialType());
        $this->assertTrue(MonsterType::WATER->isSpecialType());
        $this->assertTrue(MonsterType::ELECTRIC->isSpecialType());
        $this->assertTrue(MonsterType::GRASS->isSpecialType());
        $this->assertTrue(MonsterType::ICE->isSpecialType());
        $this->assertTrue(MonsterType::PSYCHIC->isSpecialType());

        // Physical types
        $this->assertFalse(MonsterType::NORMAL->isSpecialType());
        $this->assertFalse(MonsterType::FIGHTING->isSpecialType());
        $this->assertFalse(MonsterType::POISON->isSpecialType());
        $this->assertFalse(MonsterType::GROUND->isSpecialType());
        $this->assertFalse(MonsterType::FLYING->isSpecialType());
        $this->assertFalse(MonsterType::BUG->isSpecialType());
        $this->assertFalse(MonsterType::ROCK->isSpecialType());
        $this->assertFalse(MonsterType::GHOST->isSpecialType());
        $this->assertFalse(MonsterType::DRAGON->isSpecialType());
        $this->assertFalse(MonsterType::DARK->isSpecialType());
        $this->assertFalse(MonsterType::STEEL->isSpecialType());
        $this->assertFalse(MonsterType::FAIRY->isSpecialType());
    }

    public function test_get_color(): void
    {
        //! @section Act & Assert
        $this->assertSame('#A8A878', MonsterType::NORMAL->getColor());
        $this->assertSame('#F08030', MonsterType::FIRE->getColor());
        $this->assertSame('#6890F0', MonsterType::WATER->getColor());
        $this->assertSame('#F8D030', MonsterType::ELECTRIC->getColor());
        $this->assertSame('#78C850', MonsterType::GRASS->getColor());
        $this->assertSame('#98D8D8', MonsterType::ICE->getColor());
        $this->assertSame('#C03028', MonsterType::FIGHTING->getColor());
        $this->assertSame('#A040A0', MonsterType::POISON->getColor());
        $this->assertSame('#E0C068', MonsterType::GROUND->getColor());
        $this->assertSame('#A890F0', MonsterType::FLYING->getColor());
        $this->assertSame('#F85888', MonsterType::PSYCHIC->getColor());
        $this->assertSame('#A8B820', MonsterType::BUG->getColor());
        $this->assertSame('#B8A038', MonsterType::ROCK->getColor());
        $this->assertSame('#705898', MonsterType::GHOST->getColor());
        $this->assertSame('#7038F8', MonsterType::DRAGON->getColor());
        $this->assertSame('#705848', MonsterType::DARK->getColor());
        $this->assertSame('#B8B8D0', MonsterType::STEEL->getColor());
        $this->assertSame('#EE99AC', MonsterType::FAIRY->getColor());
    }

    public function test_to_string(): void
    {
        //! @section Act & Assert
        $this->assertSame('normal', MonsterType::NORMAL->toString());
        $this->assertSame('fire', MonsterType::FIRE->toString());
        $this->assertSame('water', MonsterType::WATER->toString());
        $this->assertSame('electric', MonsterType::ELECTRIC->toString());
        $this->assertSame('grass', MonsterType::GRASS->toString());
        $this->assertSame('ice', MonsterType::ICE->toString());
        $this->assertSame('fighting', MonsterType::FIGHTING->toString());
        $this->assertSame('poison', MonsterType::POISON->toString());
        $this->assertSame('ground', MonsterType::GROUND->toString());
        $this->assertSame('flying', MonsterType::FLYING->toString());
        $this->assertSame('psychic', MonsterType::PSYCHIC->toString());
        $this->assertSame('bug', MonsterType::BUG->toString());
        $this->assertSame('rock', MonsterType::ROCK->toString());
        $this->assertSame('ghost', MonsterType::GHOST->toString());
        $this->assertSame('dragon', MonsterType::DRAGON->toString());
        $this->assertSame('dark', MonsterType::DARK->toString());
        $this->assertSame('steel', MonsterType::STEEL->toString());
        $this->assertSame('fairy', MonsterType::FAIRY->toString());
    }

    public function test_enum_comparison(): void
    {
        //! @section Arrange
        $type1 = MonsterType::ELECTRIC;
        $type2 = MonsterType::ELECTRIC;
        $type3 = MonsterType::FIRE;

        //! @section Act & Assert
        $this->assertSame($type1, $type2);
        $this->assertNotSame($type1, $type3);
        $this->assertTrue($type1 === $type2);
        $this->assertFalse($type1 === $type3);
    }

    public function test_enum_can_be_used_in_match_statements(): void
    {
        //! @section Arrange
        $type = MonsterType::FIRE;

        //! @section Act
        $result = match ($type) {
            MonsterType::FIRE, MonsterType::WATER, MonsterType::ELECTRIC => 'special',
            MonsterType::FIGHTING, MonsterType::POISON, MonsterType::GROUND => 'physical',
            default => 'other',
        };

        //! @section Assert
        $this->assertSame('special', $result);
    }

    public function test_enum_can_be_serialized(): void
    {
        //! @section Arrange
        $type = MonsterType::GRASS;

        //! @section Act
        $serialized = serialize($type);
        $unserialized = unserialize($serialized);

        //! @section Assert
        $this->assertSame($type, $unserialized);
        $this->assertSame('grass', $unserialized->value);
    }
}
