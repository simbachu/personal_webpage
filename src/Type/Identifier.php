<?php

declare(strict_types=1);

namespace App\Type;

use InvalidArgumentException;

//! @brief Generic identifier type for domain entities
//!
//! @tparam T The type of entity this identifier represents
//!
//! This class provides type-safe identification for domain entities.
//! It encapsulates a string identifier while ensuring it's non-empty
//! and providing domain-specific validation through subclasses.
//!
//! @code
//! // Example usage:
//! $monsterId = new MonsterIdentifier('pikachu');
//! $repoId = new RepositoryIdentifier('simbachu/personal_webpage');
//!
//! echo $monsterId->getValue(); // "pikachu"
//! echo $repoId->getValue(); // "simbachu/personal_webpage"
//! @endcode
abstract class Identifier
{
    private readonly string $value;

    //! @brief Construct a new identifier instance
    //! @param value The identifier string value
    //! @throws \InvalidArgumentException If the identifier is invalid
    protected function __construct(string $value)
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw new InvalidArgumentException('Identifier cannot be empty');
        }

        // Allow subclasses to add additional validation
        $this->validate($trimmed);

        $this->value = $trimmed;
    }

    //! @brief Validate the identifier value (to be implemented by subclasses)
    //! @param value The identifier value to validate
    //! @throws \InvalidArgumentException If the identifier is invalid
    abstract protected function validate(string $value): void;

    //! @brief Get the identifier value
    //! @return string The identifier string value
    public function getValue(): string
    {
        return $this->value;
    }

    //! @brief Check if this identifier equals another
    //! @param other The other identifier to compare with
    //! @return bool True if both identifiers have the same value and type
    public function equals(Identifier $other): bool
    {
        return static::class === $other::class && $this->value === $other->getValue();
    }

    //! @brief Convert to string representation
    //! @return string The identifier value as a string
    public function __toString(): string
    {
        return $this->value;
    }

    //! @brief Create from string (convenience method for subclasses)
    //! @param value The identifier string value
    //! @return static New identifier instance
    //! @throws \InvalidArgumentException If the identifier is invalid
    public static function fromString(string $value): static
    {
        return new static($value);
    }
}
