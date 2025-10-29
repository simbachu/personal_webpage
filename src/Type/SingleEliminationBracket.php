<?php

declare(strict_types=1);

namespace App\Type;

use InvalidArgumentException;
use RuntimeException;

//! @brief Entity representing a single-elimination bracket
//!
//! Creates seeded rounds (power-of-two participants recommended). Supports
//! per-round matches, advancing winners, completion state, and overall winner.
final class SingleEliminationBracket
{
    /** @var array<int,array<int,TournamentMatch>> */
    private array $rounds = [];

    private int $currentRoundIndex = 0; // 0-based

    //! @brief Construct a bracket from top-seeded participants
    //! @param participants Seeded participants in rank order (1 is best)
    public function __construct(private readonly array $participants)
    {
        if (count($participants) < 2) {
            throw new InvalidArgumentException('Bracket requires at least 2 participants');
        }

        $this->rounds[] = $this->seedInitialRound($participants);
    }

    //! @brief Get matches for the current round
    //! @return array<TournamentMatch>
    public function getCurrentRoundMatches(): array
    {
        return $this->rounds[$this->currentRoundIndex];
    }

    //! @brief Advance to next round, generating it from winners
    public function advanceRound(): void
    {
        if ($this->isComplete()) {
            throw new RuntimeException('Bracket is already complete');
        }

        $currentMatches = $this->getCurrentRoundMatches();
        foreach ($currentMatches as $m) {
            if (!$m->isComplete()) {
                throw new RuntimeException('Cannot advance: not all matches are complete');
            }
        }

        $winners = [];
        foreach ($currentMatches as $m) {
            $winner = $m->getWinner();
            if ($winner === null) {
                throw new RuntimeException('Finalized match cannot be a draw in single elimination');
            }
            $winners[] = $winner;
        }

        if (count($winners) === 1) {
            // Tournament complete after final
            $this->currentRoundIndex++;
            return;
        }

        $nextRound = $this->pairAdjacent($winners, $this->currentRoundIndex + 1);
        $this->rounds[] = $nextRound;
        $this->currentRoundIndex++;
    }

    //! @brief Check if bracket is complete
    public function isComplete(): bool
    {
        // Complete if we have advanced past last existing matches and the last round had a single winner
        if (empty($this->rounds)) {
            return false;
        }
        // If current round index points beyond last rounds, we finished
        if ($this->currentRoundIndex >= count($this->rounds)) {
            return true;
        }

        // If we are at a round with a single match and it is complete and there is no further round
        $current = $this->rounds[$this->currentRoundIndex];
        if (count($current) === 1 && $current[0]->isComplete()) {
            // advanceRound would have incremented index; handle when called
            return false;
        }
        return false;
    }

    //! @brief Get the overall winner (after finals)
    //! @return TournamentParticipant|null Winner or null if not decided
    public function getWinner(): ?TournamentParticipant
    {
        // Winner is the winner of the last round if only one match exists and is complete
        $lastRound = end($this->rounds);
        if ($lastRound === false || count($lastRound) !== 1) {
            return null;
        }
        $final = $lastRound[0];
        return $final->isComplete() ? $final->getWinner() : null;
    }

    /**
     * @param array<int,TournamentParticipant> $participants
     * @return array<int,TournamentMatch>
     */
    private function seedInitialRound(array $participants): array
    {
        $count = count($participants);
        // Classic seeding pairs: 1vN, 4vN-3, 3vN-2, 2vN-1 to balance bracket
        $pairs = [];

        $left = 0;
        $right = $count - 1;
        $orderedPairs = [];
        while ($left < $right) {
            $orderedPairs[] = [$participants[$left], $participants[$right]];
            $left++;
            $right--;
        }

        // Reorder to standard bracket order: (1vN), (4v5), (3v6), (2v7) for 8 players
        if ($count === 8) {
            $remapped = [];
            // [$p1,$p8], [$p2,$p7], [$p3,$p6], [$p4,$p5] → reorder to [0,3,2,1]
            $remapped[] = $orderedPairs[0];
            $remapped[] = $orderedPairs[3];
            $remapped[] = $orderedPairs[2];
            $remapped[] = $orderedPairs[1];
            $orderedPairs = $remapped;
        }

        foreach ($orderedPairs as $pair) {
            $pairs[] = new TournamentMatch($pair[0], $pair[1], 1);
        }

        return $pairs;
    }

    /**
     * @param array<int,TournamentParticipant> $winners
     * @return array<int,TournamentMatch>
     */
    private function pairAdjacent(array $winners, int $roundNumber): array
    {
        $matches = [];
        for ($i = 0; $i < count($winners); $i += 2) {
            $matches[] = new TournamentMatch($winners[$i], $winners[$i + 1], $roundNumber);
        }
        return $matches;
    }
}


