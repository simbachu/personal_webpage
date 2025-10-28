<?php

declare(strict_types=1);

namespace App\Type;

use InvalidArgumentException;
use RuntimeException;

//! @brief Entity representing a tournament match between two participants
//!
//! Encapsulates a match between two participants in a specific round.
//! Maintains invariants for match state and result recording.
//!
//! @invariant participant1 !== participant2
//! @invariant round >= 0
//! @invariant result is null until match is complete
//! @invariant result is not null when match is complete
final class TournamentMatch
{
    private ?TournamentResult $result = null;

    //! @brief Construct a new tournament match
    //! @param participant1 The first participant
    //! @param participant2 The second participant
    //! @param round The round number for this match
    //! @throws \InvalidArgumentException If participants are the same or round is negative
    public function __construct(
        private readonly TournamentParticipant $participant1,
        private readonly TournamentParticipant $participant2,
        private readonly int $round
    ) {
        if ($participant1->equals($participant2)) {
            throw new InvalidArgumentException('Participants cannot be the same');
        }
        
        if ($round < 0) {
            throw new InvalidArgumentException('Round number cannot be negative');
        }
    }

    //! @brief Get the first participant
    //! @return TournamentParticipant The first participant
    public function getParticipant1(): TournamentParticipant
    {
        return $this->participant1;
    }

    //! @brief Get the second participant
    //! @return TournamentParticipant The second participant
    public function getParticipant2(): TournamentParticipant
    {
        return $this->participant2;
    }

    //! @brief Get the round number
    //! @return int The round number for this match
    public function getRound(): int
    {
        return $this->round;
    }

    //! @brief Get the match result
    //! @return TournamentResult|null The result (null if match is incomplete)
    public function getResult(): ?TournamentResult
    {
        return $this->result;
    }

    //! @brief Check if the match is complete
    //! @return bool True if the match has a result recorded
    public function isComplete(): bool
    {
        return $this->result !== null;
    }

    //! @brief Record the result of this match
    //! @param result The match result
    //! @throws \InvalidArgumentException If result is null or match is already complete
    //! @throws \DomainException If result participants don't match match participants
    public function recordResult(TournamentResult $result): void
    {
        if ($this->isComplete()) {
            throw new InvalidArgumentException('Cannot record result for completed match');
        }

        $this->validateResultParticipants($result);
        
        $this->result = $result;
        
        // Update participant statistics
        $this->updateParticipantStatistics($result);
    }

    //! @brief Get the winner of this match
    //! @return TournamentParticipant|null The winner (null for draws or incomplete matches)
    //! @throws \RuntimeException If match is not complete
    public function getWinner(): ?TournamentParticipant
    {
        if (!$this->isComplete()) {
            throw new RuntimeException('Cannot get winner from incomplete match');
        }
        
        return $this->result->getWinner();
    }

    //! @brief Check if this match equals another
    //! @param other The other match to compare with
    //! @return bool True if both matches have the same participants (order independent)
    public function equals(TournamentMatch $other): bool
    {
        return ($this->participant1->equals($other->participant1) && 
                $this->participant2->equals($other->participant2)) ||
               ($this->participant1->equals($other->participant2) && 
                $this->participant2->equals($other->participant1));
    }

    //! @brief Get string representation for debugging
    //! @return string String representation of this match
    public function toString(): string
    {
        $status = $this->isComplete() ? 'Complete' : 'Incomplete';
        $resultStr = $this->isComplete() ? ' - ' . $this->result->toString() : '';
        
        return sprintf(
            'Round %d: %s vs %s (%s)%s',
            $this->round,
            $this->participant1->getMonster()->__toString(),
            $this->participant2->getMonster()->__toString(),
            $status,
            $resultStr
        );
    }

    //! @brief Validate that result participants match match participants
    //! @param result The result to validate
    //! @throws \DomainException If result participants don't match
    private function validateResultParticipants(TournamentResult $result): void
    {
        if ($result->isDraw()) {
            return; // Draws don't have winners, so no validation needed
        }

        $winner = $result->getWinner();
        if ($winner === null) {
            throw new \DomainException('Non-draw results must have a winner');
        }

        if (!$winner->equals($this->participant1) && !$winner->equals($this->participant2)) {
            throw new \DomainException(
                'Result winner must be one of the match participants'
            );
        }
    }

    //! @brief Update participant statistics based on match result
    //! @param result The match result
    private function updateParticipantStatistics(TournamentResult $result): void
    {
        if ($result->isDraw()) {
            $this->participant1->addDraw();
            $this->participant2->addDraw();
            return;
        }

        $winner = $result->getWinner();
        if ($winner->equals($this->participant1)) {
            $this->participant1->addWin();
            $this->participant2->addLoss();
        } else {
            $this->participant1->addLoss();
            $this->participant2->addWin();
        }
    }
}
