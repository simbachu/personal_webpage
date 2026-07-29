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
        //! @section Act
        $normalValue = MonsterType::NORMAL->value;
        $fireValue = MonsterType::FIRE->value;
        $waterValue = MonsterType::WATER->value;
        $electricValue = MonsterType::ELECTRIC->value;
        $grassValue = MonsterType::GRASS->value;
        $iceValue = MonsterType::ICE->value;
        $fightingValue = MonsterType::FIGHTING->value;
        $poisonValue = MonsterType::POISON->value;
        $groundValue = MonsterType::GROUND->value;
        $flyingValue = MonsterType::FLYING->value;
        $psychicValue = MonsterType::PSYCHIC->value;
        $bugValue = MonsterType::BUG->value;
        $rockValue = MonsterType::ROCK->value;
        $ghostValue = MonsterType::GHOST->value;
        $dragonValue = MonsterType::DRAGON->value;
        $darkValue = MonsterType::DARK->value;
        $steelValue = MonsterType::STEEL->value;
        $fairyValue = MonsterType::FAIRY->value;

        //! @section Assert
        $this->assertSame('normal', $normalValue);
        $this->assertSame('fire', $fireValue);
        $this->assertSame('water', $waterValue);
        $this->assertSame('electric', $electricValue);
        $this->assertSame('grass', $grassValue);
        $this->assertSame('ice', $iceValue);
        $this->assertSame('fighting', $fightingValue);
        $this->assertSame('poison', $poisonValue);
        $this->assertSame('ground', $groundValue);
        $this->assertSame('flying', $flyingValue);
        $this->assertSame('psychic', $psychicValue);
        $this->assertSame('bug', $bugValue);
        $this->assertSame('rock', $rockValue);
        $this->assertSame('ghost', $ghostValue);
        $this->assertSame('dragon', $dragonValue);
        $this->assertSame('dark', $darkValue);
        $this->assertSame('steel', $steelValue);
        $this->assertSame('fairy', $fairyValue);
    }

    public function test_from_string_with_valid_types(): void
    {
        //! @section Act
        $normalFromString = MonsterType::fromString('normal');
        $fireFromString = MonsterType::fromString('fire');
        $waterFromString = MonsterType::fromString('water');
        $electricFromString = MonsterType::fromString('electric');
        $grassFromString = MonsterType::fromString('grass');
        $iceFromString = MonsterType::fromString('ice');
        $fightingFromString = MonsterType::fromString('fighting');
        $poisonFromString = MonsterType::fromString('poison');
        $groundFromString = MonsterType::fromString('ground');
        $flyingFromString = MonsterType::fromString('flying');
        $psychicFromString = MonsterType::fromString('psychic');
        $bugFromString = MonsterType::fromString('bug');
        $rockFromString = MonsterType::fromString('rock');
        $ghostFromString = MonsterType::fromString('ghost');
        $dragonFromString = MonsterType::fromString('dragon');
        $darkFromString = MonsterType::fromString('dark');
        $steelFromString = MonsterType::fromString('steel');
        $fairyFromString = MonsterType::fromString('fairy');

        //! @section Assert
        $this->assertSame(MonsterType::NORMAL, $normalFromString);
        $this->assertSame(MonsterType::FIRE, $fireFromString);
        $this->assertSame(MonsterType::WATER, $waterFromString);
        $this->assertSame(MonsterType::ELECTRIC, $electricFromString);
        $this->assertSame(MonsterType::GRASS, $grassFromString);
        $this->assertSame(MonsterType::ICE, $iceFromString);
        $this->assertSame(MonsterType::FIGHTING, $fightingFromString);
        $this->assertSame(MonsterType::POISON, $poisonFromString);
        $this->assertSame(MonsterType::GROUND, $groundFromString);
        $this->assertSame(MonsterType::FLYING, $flyingFromString);
        $this->assertSame(MonsterType::PSYCHIC, $psychicFromString);
        $this->assertSame(MonsterType::BUG, $bugFromString);
        $this->assertSame(MonsterType::ROCK, $rockFromString);
        $this->assertSame(MonsterType::GHOST, $ghostFromString);
        $this->assertSame(MonsterType::DRAGON, $dragonFromString);
        $this->assertSame(MonsterType::DARK, $darkFromString);
        $this->assertSame(MonsterType::STEEL, $steelFromString);
        $this->assertSame(MonsterType::FAIRY, $fairyFromString);
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
        //! @section Act
        $normalValid = MonsterType::isValid('normal');
        $fireValid = MonsterType::isValid('fire');
        $waterValid = MonsterType::isValid('water');
        $electricValid = MonsterType::isValid('electric');
        $grassValid = MonsterType::isValid('grass');
        $iceValid = MonsterType::isValid('ice');
        $fightingValid = MonsterType::isValid('fighting');
        $poisonValid = MonsterType::isValid('poison');
        $groundValid = MonsterType::isValid('ground');
        $flyingValid = MonsterType::isValid('flying');
        $psychicValid = MonsterType::isValid('psychic');
        $bugValid = MonsterType::isValid('bug');
        $rockValid = MonsterType::isValid('rock');
        $ghostValid = MonsterType::isValid('ghost');
        $dragonValid = MonsterType::isValid('dragon');
        $darkValid = MonsterType::isValid('dark');
        $steelValid = MonsterType::isValid('steel');
        $fairyValid = MonsterType::isValid('fairy');

        //! @section Assert
        $this->assertTrue($normalValid);
        $this->assertTrue($fireValid);
        $this->assertTrue($waterValid);
        $this->assertTrue($electricValid);
        $this->assertTrue($grassValid);
        $this->assertTrue($iceValid);
        $this->assertTrue($fightingValid);
        $this->assertTrue($poisonValid);
        $this->assertTrue($groundValid);
        $this->assertTrue($flyingValid);
        $this->assertTrue($psychicValid);
        $this->assertTrue($bugValid);
        $this->assertTrue($rockValid);
        $this->assertTrue($ghostValid);
        $this->assertTrue($dragonValid);
        $this->assertTrue($darkValid);
        $this->assertTrue($steelValid);
        $this->assertTrue($fairyValid);
    }

    public function test_is_valid_with_invalid_types(): void
    {
        //! @section Act
        $invalidValid = MonsterType::isValid('invalid');
        $emptyValid = MonsterType::isValid('');
        $electicValid = MonsterType::isValid('electic');
        $electricSpaceValid = MonsterType::isValid('electric ');
        $electricUpperValid = MonsterType::isValid(' ELECTRIC');
        $electricCapitalValid = MonsterType::isValid('Electric');

        //! @section Assert
        $this->assertFalse($invalidValid);
        $this->assertFalse($emptyValid);
        $this->assertFalse($electicValid);
        $this->assertFalse($electricSpaceValid);
        $this->assertFalse($electricUpperValid);
        $this->assertFalse($electricCapitalValid);
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
        //! @section Act
        $normalDisplayName = MonsterType::NORMAL->getDisplayName();
        $fireDisplayName = MonsterType::FIRE->getDisplayName();
        $waterDisplayName = MonsterType::WATER->getDisplayName();
        $electricDisplayName = MonsterType::ELECTRIC->getDisplayName();
        $grassDisplayName = MonsterType::GRASS->getDisplayName();
        $iceDisplayName = MonsterType::ICE->getDisplayName();
        $fightingDisplayName = MonsterType::FIGHTING->getDisplayName();
        $poisonDisplayName = MonsterType::POISON->getDisplayName();
        $groundDisplayName = MonsterType::GROUND->getDisplayName();
        $flyingDisplayName = MonsterType::FLYING->getDisplayName();
        $psychicDisplayName = MonsterType::PSYCHIC->getDisplayName();
        $bugDisplayName = MonsterType::BUG->getDisplayName();
        $rockDisplayName = MonsterType::ROCK->getDisplayName();
        $ghostDisplayName = MonsterType::GHOST->getDisplayName();
        $dragonDisplayName = MonsterType::DRAGON->getDisplayName();
        $darkDisplayName = MonsterType::DARK->getDisplayName();
        $steelDisplayName = MonsterType::STEEL->getDisplayName();
        $fairyDisplayName = MonsterType::FAIRY->getDisplayName();

        //! @section Assert
        $this->assertSame('Normal', $normalDisplayName);
        $this->assertSame('Fire', $fireDisplayName);
        $this->assertSame('Water', $waterDisplayName);
        $this->assertSame('Electric', $electricDisplayName);
        $this->assertSame('Grass', $grassDisplayName);
        $this->assertSame('Ice', $iceDisplayName);
        $this->assertSame('Fighting', $fightingDisplayName);
        $this->assertSame('Poison', $poisonDisplayName);
        $this->assertSame('Ground', $groundDisplayName);
        $this->assertSame('Flying', $flyingDisplayName);
        $this->assertSame('Psychic', $psychicDisplayName);
        $this->assertSame('Bug', $bugDisplayName);
        $this->assertSame('Rock', $rockDisplayName);
        $this->assertSame('Ghost', $ghostDisplayName);
        $this->assertSame('Dragon', $dragonDisplayName);
        $this->assertSame('Dark', $darkDisplayName);
        $this->assertSame('Steel', $steelDisplayName);
        $this->assertSame('Fairy', $fairyDisplayName);
    }

    public function test_is_physical_type(): void
    {
        //! @section Act
        // Physical types
        $normalIsPhysical = MonsterType::NORMAL->isPhysicalType();
        $fightingIsPhysical = MonsterType::FIGHTING->isPhysicalType();
        $poisonIsPhysical = MonsterType::POISON->isPhysicalType();
        $groundIsPhysical = MonsterType::GROUND->isPhysicalType();
        $flyingIsPhysical = MonsterType::FLYING->isPhysicalType();
        $bugIsPhysical = MonsterType::BUG->isPhysicalType();
        $rockIsPhysical = MonsterType::ROCK->isPhysicalType();
        $ghostIsPhysical = MonsterType::GHOST->isPhysicalType();
        $dragonIsPhysical = MonsterType::DRAGON->isPhysicalType();
        $darkIsPhysical = MonsterType::DARK->isPhysicalType();
        $steelIsPhysical = MonsterType::STEEL->isPhysicalType();
        $fairyIsPhysical = MonsterType::FAIRY->isPhysicalType();

        // Special types
        $fireIsPhysical = MonsterType::FIRE->isPhysicalType();
        $waterIsPhysical = MonsterType::WATER->isPhysicalType();
        $electricIsPhysical = MonsterType::ELECTRIC->isPhysicalType();
        $grassIsPhysical = MonsterType::GRASS->isPhysicalType();
        $iceIsPhysical = MonsterType::ICE->isPhysicalType();
        $psychicIsPhysical = MonsterType::PSYCHIC->isPhysicalType();

        //! @section Assert
        // Physical types
        $this->assertTrue($normalIsPhysical);
        $this->assertTrue($fightingIsPhysical);
        $this->assertTrue($poisonIsPhysical);
        $this->assertTrue($groundIsPhysical);
        $this->assertTrue($flyingIsPhysical);
        $this->assertTrue($bugIsPhysical);
        $this->assertTrue($rockIsPhysical);
        $this->assertTrue($ghostIsPhysical);
        $this->assertTrue($dragonIsPhysical);
        $this->assertTrue($darkIsPhysical);
        $this->assertTrue($steelIsPhysical);
        $this->assertTrue($fairyIsPhysical);

        // Special types
        $this->assertFalse($fireIsPhysical);
        $this->assertFalse($waterIsPhysical);
        $this->assertFalse($electricIsPhysical);
        $this->assertFalse($grassIsPhysical);
        $this->assertFalse($iceIsPhysical);
        $this->assertFalse($psychicIsPhysical);
    }

    public function test_is_special_type(): void
    {
        //! @section Act
        // Special types
        $fireIsSpecial = MonsterType::FIRE->isSpecialType();
        $waterIsSpecial = MonsterType::WATER->isSpecialType();
        $electricIsSpecial = MonsterType::ELECTRIC->isSpecialType();
        $grassIsSpecial = MonsterType::GRASS->isSpecialType();
        $iceIsSpecial = MonsterType::ICE->isSpecialType();
        $psychicIsSpecial = MonsterType::PSYCHIC->isSpecialType();

        // Physical types
        $normalIsSpecial = MonsterType::NORMAL->isSpecialType();
        $fightingIsSpecial = MonsterType::FIGHTING->isSpecialType();
        $poisonIsSpecial = MonsterType::POISON->isSpecialType();
        $groundIsSpecial = MonsterType::GROUND->isSpecialType();
        $flyingIsSpecial = MonsterType::FLYING->isSpecialType();
        $bugIsSpecial = MonsterType::BUG->isSpecialType();
        $rockIsSpecial = MonsterType::ROCK->isSpecialType();
        $ghostIsSpecial = MonsterType::GHOST->isSpecialType();
        $dragonIsSpecial = MonsterType::DRAGON->isSpecialType();
        $darkIsSpecial = MonsterType::DARK->isSpecialType();
        $steelIsSpecial = MonsterType::STEEL->isSpecialType();
        $fairyIsSpecial = MonsterType::FAIRY->isSpecialType();

        //! @section Assert
        // Special types
        $this->assertTrue($fireIsSpecial);
        $this->assertTrue($waterIsSpecial);
        $this->assertTrue($electricIsSpecial);
        $this->assertTrue($grassIsSpecial);
        $this->assertTrue($iceIsSpecial);
        $this->assertTrue($psychicIsSpecial);

        // Physical types
        $this->assertFalse($normalIsSpecial);
        $this->assertFalse($fightingIsSpecial);
        $this->assertFalse($poisonIsSpecial);
        $this->assertFalse($groundIsSpecial);
        $this->assertFalse($flyingIsSpecial);
        $this->assertFalse($bugIsSpecial);
        $this->assertFalse($rockIsSpecial);
        $this->assertFalse($ghostIsSpecial);
        $this->assertFalse($dragonIsSpecial);
        $this->assertFalse($darkIsSpecial);
        $this->assertFalse($steelIsSpecial);
        $this->assertFalse($fairyIsSpecial);
    }

    public function test_get_color(): void
    {
        //! @section Act
        $normalColor = MonsterType::NORMAL->getColor();
        $fireColor = MonsterType::FIRE->getColor();
        $waterColor = MonsterType::WATER->getColor();
        $electricColor = MonsterType::ELECTRIC->getColor();
        $grassColor = MonsterType::GRASS->getColor();
        $iceColor = MonsterType::ICE->getColor();
        $fightingColor = MonsterType::FIGHTING->getColor();
        $poisonColor = MonsterType::POISON->getColor();
        $groundColor = MonsterType::GROUND->getColor();
        $flyingColor = MonsterType::FLYING->getColor();
        $psychicColor = MonsterType::PSYCHIC->getColor();
        $bugColor = MonsterType::BUG->getColor();
        $rockColor = MonsterType::ROCK->getColor();
        $ghostColor = MonsterType::GHOST->getColor();
        $dragonColor = MonsterType::DRAGON->getColor();
        $darkColor = MonsterType::DARK->getColor();
        $steelColor = MonsterType::STEEL->getColor();
        $fairyColor = MonsterType::FAIRY->getColor();

        //! @section Assert
        $this->assertSame('#A8A878', $normalColor);
        $this->assertSame('#F08030', $fireColor);
        $this->assertSame('#6890F0', $waterColor);
        $this->assertSame('#F8D030', $electricColor);
        $this->assertSame('#78C850', $grassColor);
        $this->assertSame('#98D8D8', $iceColor);
        $this->assertSame('#C03028', $fightingColor);
        $this->assertSame('#A040A0', $poisonColor);
        $this->assertSame('#E0C068', $groundColor);
        $this->assertSame('#A890F0', $flyingColor);
        $this->assertSame('#F85888', $psychicColor);
        $this->assertSame('#A8B820', $bugColor);
        $this->assertSame('#B8A038', $rockColor);
        $this->assertSame('#705898', $ghostColor);
        $this->assertSame('#7038F8', $dragonColor);
        $this->assertSame('#705848', $darkColor);
        $this->assertSame('#B8B8D0', $steelColor);
        $this->assertSame('#EE99AC', $fairyColor);
    }

    public function test_to_string(): void
    {
        //! @section Act
        $normalString = MonsterType::NORMAL->toString();
        $fireString = MonsterType::FIRE->toString();
        $waterString = MonsterType::WATER->toString();
        $electricString = MonsterType::ELECTRIC->toString();
        $grassString = MonsterType::GRASS->toString();
        $iceString = MonsterType::ICE->toString();
        $fightingString = MonsterType::FIGHTING->toString();
        $poisonString = MonsterType::POISON->toString();
        $groundString = MonsterType::GROUND->toString();
        $flyingString = MonsterType::FLYING->toString();
        $psychicString = MonsterType::PSYCHIC->toString();
        $bugString = MonsterType::BUG->toString();
        $rockString = MonsterType::ROCK->toString();
        $ghostString = MonsterType::GHOST->toString();
        $dragonString = MonsterType::DRAGON->toString();
        $darkString = MonsterType::DARK->toString();
        $steelString = MonsterType::STEEL->toString();
        $fairyString = MonsterType::FAIRY->toString();

        //! @section Assert
        $this->assertSame('normal', $normalString);
        $this->assertSame('fire', $fireString);
        $this->assertSame('water', $waterString);
        $this->assertSame('electric', $electricString);
        $this->assertSame('grass', $grassString);
        $this->assertSame('ice', $iceString);
        $this->assertSame('fighting', $fightingString);
        $this->assertSame('poison', $poisonString);
        $this->assertSame('ground', $groundString);
        $this->assertSame('flying', $flyingString);
        $this->assertSame('psychic', $psychicString);
        $this->assertSame('bug', $bugString);
        $this->assertSame('rock', $rockString);
        $this->assertSame('ghost', $ghostString);
        $this->assertSame('dragon', $dragonString);
        $this->assertSame('dark', $darkString);
        $this->assertSame('steel', $steelString);
        $this->assertSame('fairy', $fairyString);
    }

    public function test_enum_comparison(): void
    {
        //! @section Arrange
        $type1 = MonsterType::ELECTRIC;
        $type2 = MonsterType::ELECTRIC;
        $type3 = MonsterType::FIRE;

        //! @section Act
        $type1EqualsType2 = $type1 === $type2;
        $type1EqualsType3 = $type1 === $type3;

        //! @section Assert
        $this->assertSame($type1, $type2);
        $this->assertNotSame($type1, $type3);
        $this->assertTrue($type1EqualsType2);
        $this->assertFalse($type1EqualsType3);
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
