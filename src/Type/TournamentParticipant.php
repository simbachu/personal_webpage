<?php

declare(strict_types=1);

namespace App\Type;

use InvalidArgumentException;

//! @brief Value object representing a tournament participant
//!
//! Encapsulates a MonsterIdentifier with tournament-specific statistics.
//! Maintains invariants for score consistency and provides domain operations.
//!
//! @invariant score >= 0
//! @invariant wins >= 0
//! @invariant losses >= 0
//! @invariant draws >= 0
//! @invariant score = (wins * 3) + (draws * 1) + (losses * 0)
final class TournamentParticipant
{
    private int $score = 0;
    private int $wins = 0;
    private int $losses = 0;
    private int $draws = 0;

    //! @brief Construct a new tournament participant
    //! @param monster The monster identifier for this participant
    //! @param initialScore Initial score (defaults to 0)
    //! @throws \InvalidArgumentException If initial score is negative
    public function __construct(
        private readonly MonsterIdentifier $monster,
        int $initialScore = 0
    ) {
        if ($initialScore < 0) {
            throw new InvalidArgumentException('Initial score cannot be negative');
        }
        $this->score = $initialScore;
    }

    //! @brief Get the monster identifier
    //! @return MonsterIdentifier The monster this participant represents
    public function getMonster(): MonsterIdentifier
    {
        return $this->monster;
    }

    //! @brief Get the current score
    //! @return int The participant's current score
    public function getScore(): int
    {
        return $this->score;
    }

    //! @brief Get the number of wins
    //! @return int The number of wins
    public function getWins(): int
    {
        return $this->wins;
    }

    //! @brief Get the number of losses
    //! @return int The number of losses
    public function getLosses(): int
    {
        return $this->losses;
    }

    //! @brief Get the number of draws
    //! @return int The number of draws
    public function getDraws(): int
    {
        return $this->draws;
    }

    //! @brief Add a win to this participant's record
    //! @throws \DomainException If the operation would violate invariants
    public function addWin(): void
    {
        $this->wins++;
        $this->score += 3;
        $this->assertInvariants();
    }

    //! @brief Add a loss to this participant's record
    //! @throws \DomainException If the operation would violate invariants
    public function addLoss(): void
    {
        $this->losses++;
        // Score remains unchanged for losses
        $this->assertInvariants();
    }

    //! @brief Add a draw to this participant's record
    //! @throws \DomainException If the operation would violate invariants
    public function addDraw(): void
    {
        $this->draws++;
        $this->score += 1;
        $this->assertInvariants();
    }

    //! @brief Reset all statistics to zero
    public function reset(): void
    {
        $this->score = 0;
        $this->wins = 0;
        $this->losses = 0;
        $this->draws = 0;
        $this->assertInvariants();
    }

    //! @brief Check if this participant equals another
    //! @param other The other participant to compare with
    //! @return bool True if both participants represent the same monster
    public function equals(TournamentParticipant $other): bool
    {
        return $this->monster->equals($other->monster);
    }

    //! @brief Get string representation for debugging
    //! @return string String representation of this participant
    public function toString(): string
    {
        return sprintf(
            '%s (Score: %d, W:%d L:%d D:%d)',
            $this->monster->__toString(),
            $this->score,
            $this->wins,
            $this->losses,
            $this->draws
        );
    }

    //! @brief Assert that all invariants are maintained
    //! @throws \DomainException If any invariant is violated
    private function assertInvariants(): void
    {
        if ($this->score < 0) {
            throw new \DomainException('Score cannot be negative');
        }
        if ($this->wins < 0) {
            throw new \DomainException('Wins cannot be negative');
        }
        if ($this->losses < 0) {
            throw new \DomainException('Losses cannot be negative');
        }
        if ($this->draws < 0) {
            throw new \DomainException('Draws cannot be negative');
        }

        $expectedScore = ($this->wins * 3) + ($this->draws * 1) + ($this->losses * 0);
        if ($this->score !== $expectedScore) {
            throw new \DomainException(
                sprintf(
                    'Score invariant violated: expected %d, got %d',
                    $expectedScore,
                    $this->score
                )
            );
        }
    }
}
