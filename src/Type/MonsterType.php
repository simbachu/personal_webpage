<?php

declare(strict_types=1);

namespace App\Type;

//! @brief Enum representing valid Pokemon monster types
//!
//! This enum provides type safety for Pokemon types, preventing typos and ensuring
//! only valid Pokemon types can be used. Based on the official Pokemon type system.
//!
//! @code
//! // Example usage:
//! $type = MonsterType::ELECTRIC;
//! echo $type->value; // "electric"
//!
//! // Use in MonsterData
//! $monster = new MonsterData(
//!     id: 25,
//!     name: 'Pikachu',
//!     image: 'https://example.com/pikachu.png',
//!     type1: MonsterType::ELECTRIC,
//!     type2: MonsterType::FLYING
//! );
//! @endcode
enum MonsterType: string
{
    // Normal types
    case NORMAL = 'normal';
    case FIRE = 'fire';
    case WATER = 'water';
    case ELECTRIC = 'electric';
    case GRASS = 'grass';
    case ICE = 'ice';
    case FIGHTING = 'fighting';
    case POISON = 'poison';
    case GROUND = 'ground';
    case FLYING = 'flying';
    case PSYCHIC = 'psychic';
    case BUG = 'bug';
    case ROCK = 'rock';
    case GHOST = 'ghost';
    case DRAGON = 'dragon';
    case DARK = 'dark';
    case STEEL = 'steel';
    case FAIRY = 'fairy';

    //! @brief Get all valid monster types as an array
    //! @return string[] Array of all monster type values
    public static function getAllValues(): array
    {
        return array_column(self::cases(), 'value');
    }

    //! @brief Check if a string represents a valid monster type
    //! @param typeName The monster type string to validate
    //! @return bool True if the monster type is valid
    public static function isValid(string $typeName): bool
    {
        return in_array($typeName, self::getAllValues(), true);
    }

    //! @brief Create MonsterType from string with validation
    //! @param typeName The monster type string
    //! @return self The corresponding MonsterType enum case
    //! @throws \InvalidArgumentException If the monster type is not valid
    public static function fromString(string $typeName): self
    {
        return match ($typeName) {
            'normal' => self::NORMAL,
            'fire' => self::FIRE,
            'water' => self::WATER,
            'electric' => self::ELECTRIC,
            'grass' => self::GRASS,
            'ice' => self::ICE,
            'fighting' => self::FIGHTING,
            'poison' => self::POISON,
            'ground' => self::GROUND,
            'flying' => self::FLYING,
            'psychic' => self::PSYCHIC,
            'bug' => self::BUG,
            'rock' => self::ROCK,
            'ghost' => self::GHOST,
            'dragon' => self::DRAGON,
            'dark' => self::DARK,
            'steel' => self::STEEL,
            'fairy' => self::FAIRY,
            default => throw new \InvalidArgumentException(
                "Invalid monster type: '{$typeName}'. Valid types are: " . implode(', ', self::getAllValues())
            ),
        };
    }

    //! @brief Get the display name of this monster type (capitalized)
    //! @return string Capitalized display name
    public function getDisplayName(): string
    {
        return ucfirst($this->value);
    }

    //! @brief Check if this is a physical type (affects type effectiveness)
    //! @return bool True if this is a physical type
    public function isPhysicalType(): bool
    {
        return match ($this) {
            self::NORMAL, self::FIGHTING, self::POISON, self::GROUND,
            self::FLYING, self::BUG, self::ROCK, self::GHOST,
            self::DRAGON, self::DARK, self::STEEL, self::FAIRY => true,
            default => false,
        };
    }

    //! @brief Check if this is a special type (affects type effectiveness)
    //! @return bool True if this is a special type
    public function isSpecialType(): bool
    {
        return match ($this) {
            self::FIRE, self::WATER, self::ELECTRIC, self::GRASS,
            self::ICE, self::PSYCHIC => true,
            default => false,
        };
    }

    //! @brief Get the color associated with this type (for UI theming)
    //! @return string CSS color or color name
    public function getColor(): string
    {
        return match ($this) {
            self::NORMAL => '#A8A878',
            self::FIRE => '#F08030',
            self::WATER => '#6890F0',
            self::ELECTRIC => '#F8D030',
            self::GRASS => '#78C850',
            self::ICE => '#98D8D8',
            self::FIGHTING => '#C03028',
            self::POISON => '#A040A0',
            self::GROUND => '#E0C068',
            self::FLYING => '#A890F0',
            self::PSYCHIC => '#F85888',
            self::BUG => '#A8B820',
            self::ROCK => '#B8A038',
            self::GHOST => '#705898',
            self::DRAGON => '#7038F8',
            self::DARK => '#705848',
            self::STEEL => '#B8B8D0',
            self::FAIRY => '#EE99AC',
        };
    }

    //! @brief Convert to string representation
    //! @return string The monster type value
    public function toString(): string
    {
        return $this->value;
    }
}
