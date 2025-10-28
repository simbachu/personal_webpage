<?php

declare(strict_types=1);

namespace App\Type;

use InvalidArgumentException;
use RuntimeException;

//! @brief Value object representing a tournament match result
//!
//! Encapsulates the outcome of a match between two participants.
//! Maintains invariants for valid outcomes and score calculations.
//!
//! @invariant outcome is one of: 'win', 'loss', 'draw'
//! @invariant winner is null only when outcome is 'draw'
//! @invariant winner is not null when outcome is 'win' or 'loss'
final class TournamentResult
{
    private readonly int $winnerScore;
    private readonly int $loserScore;

    //! @brief Construct a new tournament result
    //! @param outcome The match outcome ('win', 'loss', or 'draw')
    //! @param winner The winning participant (null for draws)
    //! @throws \InvalidArgumentException If outcome is invalid or winner is inconsistent
    public function __construct(
        private readonly string $outcome,
        private readonly ?TournamentParticipant $winner
    ) {
        $this->validateOutcome($outcome);
        $this->validateWinnerConsistency($outcome, $winner);
        
        // Calculate scores based on outcome
        switch ($outcome) {
            case 'win':
                $this->winnerScore = 3;
                $this->loserScore = 0;
                break;
            case 'loss':
                $this->winnerScore = 0;
                $this->loserScore = 0;
                break;
            case 'draw':
                $this->winnerScore = 1;
                $this->loserScore = 1;
                break;
            default:
                throw new InvalidArgumentException("Invalid outcome: $outcome");
        }
    }

    //! @brief Get the match outcome
    //! @return string The outcome ('win', 'loss', or 'draw')
    public function getOutcome(): string
    {
        return $this->outcome;
    }

    //! @brief Get the winning participant
    //! @return TournamentParticipant|null The winner (null for draws)
    public function getWinner(): ?TournamentParticipant
    {
        return $this->winner;
    }

    //! @brief Get the score for the winner
    //! @return int The score awarded to the winner
    public function getWinnerScore(): int
    {
        return $this->winnerScore;
    }

    //! @brief Get the score for the loser
    //! @return int The score awarded to the loser
    public function getLoserScore(): int
    {
        return $this->loserScore;
    }

    //! @brief Check if this result represents a win
    //! @return bool True if the outcome is 'win'
    public function isWin(): bool
    {
        return $this->outcome === 'win';
    }

    //! @brief Check if this result represents a loss
    //! @return bool True if the outcome is 'loss'
    public function isLoss(): bool
    {
        return $this->outcome === 'loss';
    }

    //! @brief Check if this result represents a draw
    //! @return bool True if the outcome is 'draw'
    public function isDraw(): bool
    {
        return $this->outcome === 'draw';
    }

    //! @brief Get string representation for debugging
    //! @return string String representation of this result
    public function toString(): string
    {
        if ($this->isDraw()) {
            return 'Draw (1-1)';
        }
        
        $winnerName = $this->winner ? $this->winner->getMonster()->__toString() : 'Unknown';
        return sprintf(
            '%s wins (%d-%d)',
            $winnerName,
            $this->winnerScore,
            $this->loserScore
        );
    }

    //! @brief Validate that the outcome is valid
    //! @param outcome The outcome to validate
    //! @throws \InvalidArgumentException If the outcome is invalid
    private function validateOutcome(string $outcome): void
    {
        $validOutcomes = ['win', 'loss', 'draw'];
        if (!in_array($outcome, $validOutcomes, true)) {
            throw new InvalidArgumentException(
                "Invalid outcome '$outcome'. Must be one of: " . implode(', ', $validOutcomes)
            );
        }
    }

    //! @brief Validate that winner is consistent with outcome
    //! @param outcome The match outcome
    //! @param winner The winning participant
    //! @throws \InvalidArgumentException If winner is inconsistent with outcome
    private function validateWinnerConsistency(string $outcome, ?TournamentParticipant $winner): void
    {
        if ($outcome === 'draw' && $winner !== null) {
            throw new InvalidArgumentException('Winner must be null for draw outcomes');
        }
        
        if (($outcome === 'win' || $outcome === 'loss') && $winner === null) {
            throw new InvalidArgumentException('Winner cannot be null for win/loss outcomes');
        }
    }
}
