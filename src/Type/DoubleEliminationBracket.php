<?php

declare(strict_types=1);

namespace App\Type;

use InvalidArgumentException;
use RuntimeException;

//! @brief Double-elimination bracket (winners/losers brackets, grand finals with reset)
final class DoubleEliminationBracket
{
    private string $phase = 'WB-R1';
    /** @var array<int,TournamentMatch> */
    private array $currentMatches = [];

    /** @var array<int,TournamentParticipant> */
    private array $wbWinners = [];
    /** @var array<int,TournamentParticipant> */
    private array $wbLosers = [];
    /** @var array<int,TournamentParticipant> */
    private array $lbWinners = [];

    private ?TournamentParticipant $wbChampion = null;
    private ?TournamentParticipant $lbChampion = null;
    private ?TournamentParticipant $winner = null;

    public function __construct(private readonly array $participants, private readonly bool $enableReset = true)
    {
        if (count($participants) < 2) {
            throw new InvalidArgumentException('Double elimination requires at least 2 participants');
        }
        if ((count($participants) & (count($participants) - 1)) !== 0) {
            throw new InvalidArgumentException('Only power-of-two participant counts are supported for now');
        }
        // Seed WB round 1 like single-elim top bracket
        $this->currentMatches = $this->seedWinnersRound1($participants);
    }

    //! @return array<TournamentMatch>
    public function getCurrentRoundMatches(): array
    {
        return $this->currentMatches;
    }

    public function advanceRound(): void
    {
        if ($this->winner !== null) {
            throw new RuntimeException('Bracket complete');
        }

        // Validate all matches complete
        foreach ($this->currentMatches as $m) {
            if (!$m->isComplete()) {
                throw new RuntimeException('Cannot advance: not all matches complete');
            }
        }

        switch ($this->phase) {
            case 'WB-R1':
                $this->collectWbResults();
                $this->phase = 'LB-R1';
                $this->currentMatches = $this->pairAdjacent($this->wbLosers, 1);
                break;
            case 'LB-R1':
                $this->lbWinners = $this->collectWinners($this->currentMatches);
                $this->phase = 'WB-R2';
                $this->currentMatches = $this->pairAdjacent($this->wbWinners, 2);
                // reset accumulators
                $this->wbWinners = [];
                break;
            case 'WB-R2':
                $wbW = $this->collectWinners($this->currentMatches);
                $wbL = $this->collectLosers($this->currentMatches);
                $this->phase = 'LB-R2';
                // Pair LB winners from previous LB round versus WB losers in order
                $this->currentMatches = $this->zipPair($this->lbWinners, $wbL, 2);
                $this->wbWinners = $wbW; // carry forward to WB-Final
                break;
            case 'LB-R2':
                $this->lbWinners = $this->collectWinners($this->currentMatches);
                $this->phase = 'LB-R3';
                $this->currentMatches = $this->pairAdjacent($this->lbWinners, 3);
                break;
            case 'LB-R3':
                $this->lbWinners = $this->collectWinners($this->currentMatches);
                $this->phase = 'WB-Final';
                $this->currentMatches = $this->pairAdjacent($this->wbWinners, 3);
                break;
            case 'WB-Final':
                $this->wbChampion = $this->collectWinners($this->currentMatches)[0];
                $wbFinalLoser = $this->collectLosers($this->currentMatches)[0];
                $this->phase = 'LB-Final';
                // LB final: last LB winner vs WB final loser
                $this->currentMatches = [new TournamentMatch($this->lbWinners[0], $wbFinalLoser, 4)];
                break;
            case 'LB-Final':
                $this->lbChampion = $this->collectWinners($this->currentMatches)[0];
                $this->phase = 'GF-1';
                $this->currentMatches = [new TournamentMatch($this->wbChampion, $this->lbChampion, 5)];
                break;
            case 'GF-1':
                $gfWinner = $this->collectWinners($this->currentMatches)[0];
                if ($gfWinner === $this->wbChampion) {
                    $this->winner = $gfWinner;
                    $this->currentMatches = [];
                } else {
                    if ($this->enableReset === false) {
                        // No reset: LB champion winning GF-1 decides the tournament
                        $this->winner = $gfWinner;
                        $this->currentMatches = [];
                        break;
                    }
                    // bracket reset
                    $this->phase = 'GF-Reset';
                    $this->currentMatches = [new TournamentMatch($this->wbChampion, $this->lbChampion, 6)];
                }
                break;
            case 'GF-Reset':
                $this->winner = $this->collectWinners($this->currentMatches)[0];
                $this->currentMatches = [];
                break;
            default:
                throw new RuntimeException('Unknown phase');
        }
    }

    public function isComplete(): bool
    {
        return $this->winner !== null;
    }

    public function getWinner(): ?TournamentParticipant
    {
        return $this->winner;
    }

    /** @return array<int,TournamentMatch> */
    private function seedWinnersRound1(array $participants): array
    {
        // Reuse SingleEliminationBracket seeding order for 8 and 16
        $count = count($participants);
        $pairs = [];
        $left = 0; $right = $count - 1;
        $orderedPairs = [];
        while ($left < $right) {
            $orderedPairs[] = [$participants[$left], $participants[$right]];
            $left++; $right--;
        }
        if ($count === 8) {
            $orderedPairs = [$orderedPairs[0], $orderedPairs[3], $orderedPairs[2], $orderedPairs[1]];
        }
        foreach ($orderedPairs as $pair) {
            $pairs[] = new TournamentMatch($pair[0], $pair[1], 1);
        }
        return $pairs;
    }

    /** @return array<int,TournamentParticipant> */
    private function collectWinners(array $matches): array
    {
        $w = [];
        foreach ($matches as $m) {
            $win = $m->getWinner();
            if ($win === null) {
                throw new RuntimeException('Draws are not allowed in elimination');
            }
            $w[] = $win;
        }
        return $w;
    }

    /** @return array<int,TournamentParticipant> */
    private function collectLosers(array $matches): array
    {
        $l = [];
        foreach ($matches as $m) {
            $win = $m->getWinner();
            $p1 = $m->getParticipant1();
            $p2 = $m->getParticipant2();
            if ($win === null) {
                throw new RuntimeException('Draws are not allowed in elimination');
            }
            $l[] = $win->equals($p1) ? $p2 : $p1;
        }
        return $l;
    }

    private function collectWbResults(): void
    {
        $this->wbWinners = $this->collectWinners($this->currentMatches);
        $this->wbLosers = $this->collectLosers($this->currentMatches);
        // Prepare for next usage
        $this->lbWinners = [];
    }

    /**
     * @param array<int,TournamentParticipant> $participants
     * @return array<int,TournamentMatch>
     */
    private function pairAdjacent(array $participants, int $round): array
    {
        $matches = [];
        for ($i = 0; $i < count($participants); $i += 2) {
            $matches[] = new TournamentMatch($participants[$i], $participants[$i + 1], $round);
        }
        return $matches;
    }

    /**
     * Pair a[i] vs b[i]
     * @param array<int,TournamentParticipant> $a
     * @param array<int,TournamentParticipant> $b
     * @return array<int,TournamentMatch>
     */
    private function zipPair(array $a, array $b, int $round): array
    {
        $count = min(count($a), count($b));
        $matches = [];
        for ($i = 0; $i < $count; $i++) {
            $matches[] = new TournamentMatch($a[$i], $b[$i], $round);
        }
        return $matches;
    }
}


