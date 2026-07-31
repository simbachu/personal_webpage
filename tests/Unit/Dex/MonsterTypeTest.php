<?php

declare(strict_types=1);

namespace Tests\Unit\Dex;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use App\Dex\MonsterType;

//! @brief Compact MonsterType contracts (invalid input + sample classification)
class MonsterTypeTest extends TestCase
{
    #[DataProvider('provideValidTypes')]
    public function test_from_string_round_trips_sample_types(string $value, MonsterType $expected): void
    {
        //! @section Act
        $type = MonsterType::fromString($value);

        //! @section Assert
        $this->assertSame($expected, $type);
        $this->assertTrue(MonsterType::isValid($value));
        $this->assertSame(ucfirst($value), $type->getDisplayName());
    }

    //! @return array<string, array{0: string, 1: MonsterType}>
    public static function provideValidTypes(): array
    {
        return [
            'normal' => ['normal', MonsterType::NORMAL],
            'fire' => ['fire', MonsterType::FIRE],
            'fairy' => ['fairy', MonsterType::FAIRY],
        ];
    }

    public function test_from_string_rejects_invalid_type(): void
    {
        //! @section Arrange
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid monster type: 'invalid'");

        //! @section Act
        MonsterType::fromString('invalid');
    }

    public function test_from_string_rejects_empty_string(): void
    {
        //! @section Arrange
        $this->expectException(\InvalidArgumentException::class);

        //! @section Act
        MonsterType::fromString('');
    }

    public function test_is_valid_rejects_unknown_and_cased_values(): void
    {
        //! @section Assert
        $this->assertFalse(MonsterType::isValid('invalid'));
        $this->assertFalse(MonsterType::isValid(''));
        $this->assertFalse(MonsterType::isValid('Electric'));
        $this->assertFalse(MonsterType::isValid('fire '));
    }

    public function test_physical_special_and_color_tables_cover_all_cases(): void
    {
        //! @section Act / Assert — one compact table keeps CRAP down without enum demos
        foreach (MonsterType::cases() as $type) {
            $this->assertNotSame('', $type->getColor());
            $this->assertSame(
                $type->isPhysicalType(),
                !$type->isSpecialType(),
                $type->value . ' should be exactly one of physical/special'
            );
        }
        $this->assertSame('#A8A878', MonsterType::NORMAL->getColor());
        $this->assertSame('#F08030', MonsterType::FIRE->getColor());
        $this->assertSame('#EE99AC', MonsterType::FAIRY->getColor());
    }
}
