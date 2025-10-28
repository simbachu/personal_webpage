<?php

declare(strict_types=1);

namespace App\Type;

use InvalidArgumentException;

//! @brief Identifier for Tournament entities
//!
//! Represents a unique identifier for a Tournament entity.
//! Provides type safety when working with Tournament identifiers throughout the system.
//!
//! @code
//! // Example usage:
//! $tournamentId = TournamentIdentifier::fromString('tournament-123');
//! $service->getTournament($tournamentId);
//! @endcode
final class TournamentIdentifier extends Identifier
{
    //! @brief Validate that the Tournament identifier is valid
    //! @param value The identifier value to validate
    //! @throws \InvalidArgumentException If the identifier format is invalid
    protected function validate(string $value): void
    {
        // Allow alphanumeric characters, hyphens, and underscores
        // Tournament IDs should be reasonably formatted
        if (!preg_match('/^[a-zA-Z0-9\-_]+$/', $value)) {
            throw new InvalidArgumentException(
                'Tournament identifier must contain only alphanumeric characters, hyphens, and underscores. Got: ' . $value
            );
        }

        // Ensure it's not too long
        if (strlen($value) > 100) {
            throw new InvalidArgumentException(
                'Tournament identifier cannot exceed 100 characters. Got: ' . $value
            );
        }

        // Ensure it's not too short
        if (strlen($value) < 3) {
            throw new InvalidArgumentException(
                'Tournament identifier must be at least 3 characters. Got: ' . $value
            );
        }
    }

    //! @brief Generate a new unique tournament identifier
    //! @return static New TournamentIdentifier with unique value
    public static function generate(): static
    {
        $timestamp = time();
        $random = bin2hex(random_bytes(4));
        return new self("tournament-{$timestamp}-{$random}");
    }
}
