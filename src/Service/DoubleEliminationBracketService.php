<?php

declare(strict_types=1);

namespace App\Service;

use App\Type\MonsterIdentifier;
use InvalidArgumentException;

//! @brief Service for managing double elimination bracket structure
//!
//! Handles bracket creation, match progression, and bracket state management
//! for a 16-participant double elimination tournament.
final class DoubleEliminationBracketService
{
    //! @brief Create initial bracket structure from top 16
    //! @param participants Array of 16 monster identifiers (sorted by seed, best first)
    //! @return array{winner_bracket:array,loser_bracket:array} Bracket structure
    public function createBracket(array $participants): array
    {
        if (count($participants) !== 16) {
            throw new InvalidArgumentException('Bracket requires exactly 16 participants');
        }

        // Winner bracket: 16 → 8 → 4 → 2 → 1
        // Round 1: 8 matches (16 participants)
        // Round 2: 4 matches (8 winners)
        // Round 3: 2 matches (4 winners)
        // Round 4: 1 match (2 winners) -> Winner Bracket Champion
        
        $winnerBracket = [
            'round1' => $this->createRound1Matches($participants),
            'round2' => [], // Will be populated as matches complete
            'round3' => [],
            'round4' => [], // Winner bracket final
        ];

        // Loser bracket: Receives losers and runs parallel elimination
        // Structure is more complex due to feeding from winner bracket
        $loserBracket = [
            'round1' => [], // First losers from winner bracket round 1
            'round2' => [], // Losers from winner bracket round 2 + winners from loser round 1
            'round3' => [], // Losers from winner bracket round 3 + winners from loser round 2
            'round4' => [], // Loser bracket semi-final
            'round5' => [], // Loser bracket final (winner goes to grand finals)
        ];

        return [
            'winner_bracket' => $winnerBracket,
            'loser_bracket' => $loserBracket,
            'grand_finals' => [], // Winner bracket champ vs Loser bracket champ
        ];
    }

    //! @brief Create round 1 matches (16 participants → 8 matches)
    //! @param participants Array of 16 participants (seeded)
    //! @return array<array{id:string,participant1:MonsterIdentifier,participant2:MonsterIdentifier,winner:MonsterIdentifier|null,round:int}> Matches
    private function createRound1Matches(array $participants): array
    {
        $matches = [];
        // Standard bracket seeding: 1 vs 16, 2 vs 15, 3 vs 14, etc.
        for ($i = 0; $i < 8; $i++) {
            $matches[] = [
                'id' => "w1_" . ($i + 1),
                'participant1' => $participants[$i],
                'participant2' => $participants[15 - $i],
                'winner' => null,
                'round' => 1,
            ];
        }
        return $matches;
    }

    //! @brief Get current bracket matches ready for voting
    //! @param bracket Current bracket state
    //! @return array<array{id:string,participant1:MonsterIdentifier,participant2:MonsterIdentifier,winner:MonsterIdentifier|null,bracket:string,round:int}> Matches ready for voting
    public function getMatchesReadyForVoting(array $bracket): array
    {
        $readyMatches = [];

        // Check winner bracket matches in order
        foreach (['round1', 'round2', 'round3', 'round4'] as $round) {
            if (!isset($bracket['winner_bracket'][$round])) {
                continue;
            }
            foreach ($bracket['winner_bracket'][$round] as $match) {
                if ($match['winner'] === null) {
                    $readyMatches[] = array_merge($match, ['bracket' => 'winner']);
                    // Only return first incomplete match from first incomplete round
                    return [$readyMatches[0]];
                }
            }
        }

        // Check loser bracket matches
        foreach (['round1', 'round2', 'round3', 'round4', 'round5'] as $round) {
            if (!isset($bracket['loser_bracket'][$round])) {
                continue;
            }
            foreach ($bracket['loser_bracket'][$round] as $match) {
                if ($match['winner'] === null) {
                    $readyMatches[] = array_merge($match, ['bracket' => 'loser']);
                    return [$readyMatches[0]];
                }
            }
        }

        // Check grand finals
        if (isset($bracket['grand_finals']) && !empty($bracket['grand_finals'])) {
            foreach ($bracket['grand_finals'] as $match) {
                if ($match['winner'] === null) {
                    $readyMatches[] = array_merge($match, ['bracket' => 'grand_finals']);
                    return [$readyMatches[0]];
                }
            }
        }

        return $readyMatches;
    }

    //! @brief Record a match result and advance bracket
    //! @param bracket Current bracket state (passed by reference - will be modified)
    //! @param matchId Match identifier
    //! @param winner Winning participant
    public function recordMatchResult(array &$bracket, string $matchId, MonsterIdentifier $winner): void
    {
        // Find and update the match
        $match = $this->findMatch($bracket, $matchId);
        if ($match === null) {
            throw new InvalidArgumentException("Match not found: $matchId");
        }

        // Validate winner is one of the participants
        if (!$match['participant1']->equals($winner) && !$match['participant2']->equals($winner)) {
            throw new InvalidArgumentException('Winner must be one of the match participants');
        }

        // Update match result
        $match['winner'] = $winner;

        // Determine loser
        $loser = $match['participant1']->equals($winner) 
            ? $match['participant2'] 
            : $match['participant1'];

        // Advance bracket based on which bracket and round
        if ($match['bracket'] === 'winner') {
            $this->advanceWinnerBracket($bracket, $match, $winner, $loser);
        } elseif ($match['bracket'] === 'loser') {
            $this->advanceLoserBracket($bracket, $match, $winner, $loser);
        } else {
            // Grand finals - tournament complete
            // Match winner already updated above
        }
    }

    //! @brief Find a match by ID in the bracket and update it
    //! @param bracket Bracket state (passed by reference)
    //! @param matchId Match identifier
    //! @return array|null Match data or null if not found
    private function findMatch(array &$bracket, string $matchId): ?array
    {
        // Search winner bracket
        foreach (['round1', 'round2', 'round3', 'round4'] as $round) {
            if (!isset($bracket['winner_bracket'][$round])) {
                continue;
            }
            foreach ($bracket['winner_bracket'][$round] as $idx => &$match) {
                if ($match['id'] === $matchId) {
                    $match['bracket'] = 'winner';
                    return &$match;
                }
            }
            unset($match);
        }

        // Search loser bracket
        foreach (['round1', 'round2', 'round3', 'round4', 'round5'] as $round) {
            if (!isset($bracket['loser_bracket'][$round])) {
                continue;
            }
            foreach ($bracket['loser_bracket'][$round] as $idx => &$match) {
                if ($match['id'] === $matchId) {
                    $match['bracket'] = 'loser';
                    return &$match;
                }
            }
            unset($match);
        }

        // Search grand finals
        if (isset($bracket['grand_finals'])) {
            foreach ($bracket['grand_finals'] as $idx => &$match) {
                if ($match['id'] === $matchId) {
                    $match['bracket'] = 'grand_finals';
                    return &$match;
                }
            }
            unset($match);
        }

        return null;
    }

    //! @brief Advance winner bracket after a match result
    //! @param bracket Current bracket (passed by reference)
    //! @param match Completed match
    //! @param winner Winner
    //! @param loser Loser
    private function advanceWinnerBracket(array &$bracket, array $match, MonsterIdentifier $winner, MonsterIdentifier $loser): void
    {
        $round = $match['round'];

        // Winner advances to next winner bracket round
        if ($round === 1) {
            // Winners go to round 2, losers to loser bracket round 1
            if (!isset($bracket['winner_bracket']['round2'])) {
                $bracket['winner_bracket']['round2'] = [];
            }
            $this->addToNextRound($bracket['winner_bracket']['round2'], $match['id'], $winner);
            
            // Add loser to loser bracket round 1
            if (!isset($bracket['loser_bracket']['round1'])) {
                $bracket['loser_bracket']['round1'] = [];
            }
            $this->addLoserToBracket($bracket['loser_bracket']['round1'], $loser);
        } elseif ($round === 2) {
            // Winners to round 3, losers to loser bracket round 2
            if (!isset($bracket['winner_bracket']['round3'])) {
                $bracket['winner_bracket']['round3'] = [];
            }
            $this->addToNextRound($bracket['winner_bracket']['round3'], $match['id'], $winner);
            $this->addLoserToBracket($bracket['loser_bracket']['round2'], $loser);
        } elseif ($round === 3) {
            // Winners to round 4 (semi-final), losers to loser bracket round 3
            if (!isset($bracket['winner_bracket']['round4'])) {
                $bracket['winner_bracket']['round4'] = [];
            }
            $this->addToNextRound($bracket['winner_bracket']['round4'], $match['id'], $winner);
            $this->addLoserToBracket($bracket['loser_bracket']['round3'], $loser);
        } elseif ($round === 4) {
            // Winner bracket champion - goes to grand finals
            if (!isset($bracket['grand_finals'])) {
                $bracket['grand_finals'] = [];
            }
            $bracket['grand_finals'][] = [
                'id' => 'grand_finals_1',
                'participant1' => $winner, // Winner bracket champion
                'participant2' => null, // Will be filled by loser bracket champion
                'winner' => null,
                'round' => 1,
            ];
        }
    }

    //! @brief Advance loser bracket after a match result
    //! @param bracket Current bracket (passed by reference)
    //! @param match Completed match
    //! @param winner Winner
    //! @param loser Loser
    private function advanceLoserBracket(array &$bracket, array $match, MonsterIdentifier $winner, MonsterIdentifier $loser): void
    {
        $round = $match['round'];

        if ($round === 1) {
            // Winners go to round 2
            $this->addLoserToBracket($bracket['loser_bracket']['round2'], $winner);
        } elseif ($round === 2) {
            // Winners go to round 3
            $this->addLoserToBracket($bracket['loser_bracket']['round3'], $winner);
        } elseif ($round === 3) {
            // Winners go to round 4 (semi-final)
            $this->addLoserToBracket($bracket['loser_bracket']['round4'], $winner);
        } elseif ($round === 4) {
            // Winners go to round 5 (loser bracket final)
            $this->addLoserToBracket($bracket['loser_bracket']['round5'], $winner);
        } elseif ($round === 5) {
            // Loser bracket champion - goes to grand finals
            if (isset($bracket['grand_finals'][0])) {
                $bracket['grand_finals'][0]['participant2'] = $winner;
            }
        }
    }

    //! @brief Add winner to next round match
    //! @param nextRoundMatches Array of next round matches
    //! @param matchId Current match ID (for pairing logic)
    //! @param winner Winner to add
    private function addToNextRound(array &$nextRoundMatches, string $matchId, MonsterIdentifier $winner): void
    {
        // Find if there's an incomplete match to add to
        foreach ($nextRoundMatches as &$nextMatch) {
            if ($nextMatch['participant1'] === null) {
                $nextMatch['participant1'] = $winner;
                return;
            } elseif ($nextMatch['participant2'] === null) {
                $nextMatch['participant2'] = $winner;
                return;
            }
        }

        // Create new match
        $matchNum = count($nextRoundMatches) + 1;
        $nextRoundMatches[] = [
            'id' => 'w' . (count($nextRoundMatches) + 2) . '_' . $matchNum,
            'participant1' => $winner,
            'participant2' => null,
            'winner' => null,
            'round' => count($nextRoundMatches) + 2,
        ];
    }

    //! @brief Add loser to loser bracket round
    //! @param roundMatches Array of round matches
    //! @param loser Loser to add
    private function addLoserToBracket(array &$roundMatches, MonsterIdentifier $loser): void
    {
        // Find if there's an incomplete match to add to
        foreach ($roundMatches as &$match) {
            if ($match['participant1'] === null) {
                $match['participant1'] = $loser;
                return;
            } elseif ($match['participant2'] === null) {
                $match['participant2'] = $loser;
                return;
            }
        }

        // Create new match
        $roundNum = $this->getRoundNumber($roundMatches);
        $matchNum = count($roundMatches) + 1;
        $roundMatches[] = [
            'id' => 'l' . $roundNum . '_' . $matchNum,
            'participant1' => $loser,
            'participant2' => null,
            'winner' => null,
            'round' => $roundNum,
        ];
    }

    //! @brief Get round number from matches array (infer from match IDs or count)
    //! @param matches Array of matches
    //! @return int Round number
    private function getRoundNumber(array $matches): int
    {
        if (empty($matches)) {
            return 1;
        }
        return $matches[0]['round'] ?? 1;
    }

    //! @brief Check if bracket is complete (grand finals winner determined)
    //! @param bracket Bracket state
    //! @return bool True if bracket is complete
    public function isBracketComplete(array $bracket): bool
    {
        return isset($bracket['grand_finals'][0]['winner']) 
            && $bracket['grand_finals'][0]['winner'] !== null;
    }
}

