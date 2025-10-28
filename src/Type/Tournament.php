<?php

declare(strict_types=1);

namespace App\Type;

use InvalidArgumentException;
use RuntimeException;

//! @brief Entity representing a tournament
//!
//! Encapsulates a tournament with participants, rounds, and matches.
//! Maintains invariants for tournament state and progression.
//!
//! @invariant participants count >= 1
//! @invariant currentRound >= 0
//! @invariant currentRound <= totalRounds
//! @invariant isComplete() === (currentRound >= totalRounds)
final class Tournament
{
    private int $currentRound = 0;
    private readonly int $totalRounds;

    //! @brief Construct a new tournament
    //! @param id The tournament identifier
    //! @param userEmail The email of the user who created this tournament
    //! @param participants Array of tournament participants
    //! @param totalRounds Total number of rounds for this tournament
    //! @throws \InvalidArgumentException If participants is empty or totalRounds is invalid
    public function __construct(
        private readonly TournamentIdentifier $id,
        private readonly string $userEmail,
        private readonly array $participants,
        int $totalRounds
    ) {
        if (empty($participants)) {
            throw new InvalidArgumentException('Tournament must have at least one participant');
        }

        if ($totalRounds < 0) {
            throw new InvalidArgumentException('Total rounds cannot be negative');
        }

        $this->totalRounds = $totalRounds;
        $this->assertInvariants();
    }

    //! @brief Get the tournament identifier
    //! @return TournamentIdentifier The tournament ID
    public function getId(): TournamentIdentifier
    {
        return $this->id;
    }

    //! @brief Get the user email
    //! @return string The email of the user who created this tournament
    public function getUserEmail(): string
    {
        return $this->userEmail;
    }

    //! @brief Get the participants
    //! @return array<TournamentParticipant> Array of tournament participants
    public function getParticipants(): array
    {
        return $this->participants;
    }

    //! @brief Get the number of participants
    //! @return int The number of participants
    public function getParticipantCount(): int
    {
        return count($this->participants);
    }

    //! @brief Get the current round number
    //! @return int The current round (0-based)
    public function getCurrentRound(): int
    {
        return $this->currentRound;
    }

    //! @brief Get the total number of rounds
    //! @return int The total number of rounds
    public function getTotalRounds(): int
    {
        return $this->totalRounds;
    }

    //! @brief Check if the tournament is complete
    //! @return bool True if all rounds have been completed
    public function isComplete(): bool
    {
        return $this->currentRound >= $this->totalRounds;
    }

    //! @brief Advance to the next round
    //! @throws \RuntimeException If tournament is already complete
    public function advanceRound(): void
    {
        if ($this->isComplete()) {
            throw new RuntimeException('Cannot advance round: tournament is already complete');
        }

        $this->currentRound++;
        $this->assertInvariants();
    }

    //! @brief Get a participant by monster identifier
    //! @param monster The monster identifier to find
    //! @return TournamentParticipant|null The participant or null if not found
    public function getParticipant(MonsterIdentifier $monster): ?TournamentParticipant
    {
        foreach ($this->participants as $participant) {
            if ($participant->getMonster()->equals($monster)) {
                return $participant;
            }
        }
        return null;
    }

    //! @brief Get current standings sorted by score (descending)
    //! @return array<TournamentParticipant> Participants sorted by score
    public function getStandings(): array
    {
        $standings = $this->participants;
        
        usort($standings, function (TournamentParticipant $a, TournamentParticipant $b) {
            $scoreComparison = $b->getScore() <=> $a->getScore();
            if ($scoreComparison !== 0) {
                return $scoreComparison;
            }
            
            // Tie-breaker: more wins
            $winsComparison = $b->getWins() <=> $a->getWins();
            if ($winsComparison !== 0) {
                return $winsComparison;
            }
            
            // Final tie-breaker: fewer losses
            return $a->getLosses() <=> $b->getLosses();
        });
        
        return $standings;
    }

    //! @brief Get string representation for debugging
    //! @return string String representation of this tournament
    public function toString(): string
    {
        $status = $this->isComplete() ? 'Complete' : 'In Progress';
        return sprintf(
            'Tournament %s (%s) - Round %d/%d - %d participants - %s',
            $this->id->__toString(),
            $this->userEmail,
            $this->currentRound,
            $this->totalRounds,
            $this->getParticipantCount(),
            $status
        );
    }

    //! @brief Assert that all invariants are maintained
    //! @throws \DomainException If any invariant is violated
    private function assertInvariants(): void
    {
        if (empty($this->participants)) {
            throw new \DomainException('Tournament must have at least one participant');
        }

        if ($this->currentRound < 0) {
            throw new \DomainException('Current round cannot be negative');
        }

        if ($this->currentRound > $this->totalRounds) {
            throw new \DomainException('Current round cannot exceed total rounds');
        }

        if ($this->isComplete() !== ($this->currentRound >= $this->totalRounds)) {
            throw new \DomainException('Completion state invariant violated');
        }
    }
}
