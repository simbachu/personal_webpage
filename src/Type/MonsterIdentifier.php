<?php

declare(strict_types=1);

namespace App\Type;

use InvalidArgumentException;

//! @brief Identifier for Monster entities
//!
//! Represents a unique identifier for a Monster entity, which can be either
//! a numeric ID (e.g., "25") or a name (e.g., "pikachu"). This provides
//! type safety when working with Monster identifiers throughout the system.
//!
//! @code
//! // Example usage:
//! $id = MonsterIdentifier::fromString('pikachu');
//! $numeric = MonsterIdentifier::fromString('25');
//!
//! echo $id->getValue(); // "pikachu"
//! echo $numeric->getValue(); // "25"
//!
//! // Use in service calls
//! $service->fetchMonster($id);
//! @endcode
final class MonsterIdentifier extends Identifier
{
    //! @brief Validate that the Monster identifier is valid
    //! @param value The identifier value to validate
    //! @throws \InvalidArgumentException If the identifier format is invalid
    protected function validate(string $value): void
    {
        // Allow alphanumeric characters, hyphens, and underscores
        // This covers both numeric IDs and Pokemon names
        if (!preg_match('/^[a-zA-Z0-9\-_]+$/', $value)) {
            throw new InvalidArgumentException(
                'Monster identifier must contain only alphanumeric characters, hyphens, and underscores. Got: ' . $value
            );
        }

        // Ensure it's not too long (reasonable limit for Pokemon names/IDs)
        if (strlen($value) > 50) {
            throw new InvalidArgumentException(
                'Monster identifier cannot exceed 50 characters. Got: ' . $value
            );
        }
    }

    //! @brief Check if this identifier represents a numeric ID
    //! @return bool True if the identifier is numeric
    public function isNumeric(): bool
    {
        return is_numeric($this->getValue());
    }

    //! @brief Check if this identifier represents a name
    //! @return bool True if the identifier is not numeric (i.e., a name)
    public function isName(): bool
    {
        return !$this->isNumeric();
    }

    //! @brief Get the numeric ID if this identifier is numeric
    //! @return int The numeric ID
    //! @throws \InvalidArgumentException If this identifier is not numeric
    public function getNumericId(): int
    {
        if (!$this->isNumeric()) {
            throw new InvalidArgumentException(
                'Cannot get numeric ID from non-numeric identifier: ' . $this->getValue()
            );
        }

        return (int) $this->getValue();
    }

    //! @brief Get the name if this identifier is a name
    //! @return string The name identifier
    //! @throws \InvalidArgumentException If this identifier is numeric
    public function getName(): string
    {
        if ($this->isNumeric()) {
            throw new InvalidArgumentException(
                'Cannot get name from numeric identifier: ' . $this->getValue()
            );
        }

        return $this->getValue();
    }

    //! @brief Create from a numeric ID
    //! @param id The numeric ID
    //! @return static New MonsterIdentifier with numeric ID
    public static function fromNumericId(int $id): static
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Monster ID must be positive, got: ' . $id);
        }

        return new self((string) $id);
    }

    //! @brief Create from a name
    //! @param name The Monster name
    //! @return static New MonsterIdentifier with name
    public static function fromName(string $name): static
    {
        return new self($name);
    }
}
