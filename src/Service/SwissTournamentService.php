<?php

declare(strict_types=1);

namespace App\Service;

use App\Type\MonsterIdentifier;
use App\Type\Tournament;
use App\Type\TournamentIdentifier;
use App\Type\TournamentParticipant;
use InvalidArgumentException;

//! @brief Swiss tournament service implementing pairing algorithms and tournament management
//!
//! Provides Swiss-style tournament functionality including pairing generation,
//! scoring, and tournament state management. Swiss tournaments ensure balanced
//! competition by pairing participants with similar records.
//!
//! @invariant All pairings avoid repeat matchups when possible
//! @invariant Pairings are based on current standings for balanced competition
//! @invariant Scoring follows 3-0-1 system (win-loss-draw)
final class SwissTournamentService implements SwissPairingInterface
{
    //! @brief Generate pairings for the next round
    //! @param participants Array of participants to pair
    //! @param previousMatchups Array of previous matchups to avoid repeats
    //! @param standings Current standings (monster => score)
    //! @return array<array<MonsterIdentifier>> Array of pairings
    public function generatePairings(
        array $participants,
        array $previousMatchups = [],
        array $standings = []
    ): array {
        if (empty($participants)) {
            throw new InvalidArgumentException('Cannot generate pairings for empty participants list');
        }

        $participantCount = count($participants);
        
        // Single participant gets a bye
        if ($participantCount === 1) {
            return [[$participants[0]]];
        }

        // Sort participants by standings if provided
        if (!empty($standings)) {
            $participants = $this->sortParticipantsByStandings($participants, $standings);
        }

        $pairings = [];
        $used = array_fill(0, $participantCount, false);
        $targetPairings = (int) ceil($participantCount / 2);

        // Pair participants
        for ($i = 0; $i < $participantCount && count($pairings) < $targetPairings; $i++) {
            if ($used[$i]) {
                continue;
            }

            $participant1 = $participants[$i];
            $used[$i] = true;

            // Find best opponent
            $bestOpponentIndex = $this->findBestOpponent(
                $participants,
                $used,
                $i,
                $previousMatchups,
                $standings
            );

            if ($bestOpponentIndex !== null) {
                $participant2 = $participants[$bestOpponentIndex];
                $used[$bestOpponentIndex] = true;
                $pairings[] = [$participant1, $participant2];
            } else {
                // No opponent found, participant gets a bye
                $pairings[] = [$participant1];
            }
        }

        return $pairings;
    }

    //! @brief Calculate total rounds needed for a Swiss tournament
    //! @param participantCount Number of participants
    //! @return int Number of rounds needed
    public function calculateTotalRounds(int $participantCount): int
    {
        if ($participantCount <= 0) {
            throw new InvalidArgumentException('Participant count must be positive');
        }

        if ($participantCount === 1) {
            return 0; // Single participant tournament is immediately complete
        }

        // Requirement: Play log(n) rounds
        // Using ceil(log2(n)) for Swiss tournament rounds
        // This ensures enough rounds for proper ranking while keeping tournament manageable
        $logRounds = (int) ceil(log($participantCount, 2));
        
        // Ensure minimum of 3 rounds for meaningful competition
        // Cap at reasonable maximum (e.g., 8 rounds) to prevent excessive length
        return max(3, min(8, $logRounds));
    }

    //! @brief Sort standings by tie-breaker criteria
    //! @param standings Current standings (monster string => score)
    //! @param participants All participants for tie-breaking
    //! @return array<array{monster:MonsterIdentifier,score:int}> Sorted standings
    public function sortStandingsByTieBreaker(array $standings, array $participants): array
    {
        $sortedStandings = [];
        
        foreach ($standings as $monsterString => $score) {
            // Find the MonsterIdentifier for this string
            $monster = null;
            foreach ($participants as $participant) {
                if ($participant->__toString() === $monsterString) {
                    $monster = $participant;
                    break;
                }
            }
            
            if ($monster !== null) {
                $sortedStandings[] = [
                    'monster' => $monster,
                    'score' => $score
                ];
            }
        }

        // Sort by score (descending), then by monster name for deterministic ordering
        usort($sortedStandings, function (array $a, array $b) {
            $scoreComparison = $b['score'] <=> $a['score'];
            if ($scoreComparison !== 0) {
                return $scoreComparison;
            }
            
            // Tie-breaker: alphabetical order of monster names
            $monsterA = $a['monster'];
            $monsterB = $b['monster'];
            assert($monsterA instanceof MonsterIdentifier);
            assert($monsterB instanceof MonsterIdentifier);
            return strcmp($monsterA->__toString(), $monsterB->__toString());
        });

        return $sortedStandings;
    }

    //! @brief Get score for a match result
    //! @param result The match result ('win', 'loss', 'draw')
    //! @return int The score for this result
    public function getScoreForResult(string $result): int
    {
        return match ($result) {
            'win' => 3,
            'loss' => 0,
            'draw' => 1,
            default => throw new InvalidArgumentException("Invalid result: $result")
        };
    }

    //! @brief Create a tournament
    //! @param participants Array of monster identifiers
    //! @param userEmail Email of the user creating the tournament
    //! @return Tournament The created tournament
    public function createTournament(array $participants, string $userEmail): Tournament
    {
        if (empty($participants)) {
            throw new InvalidArgumentException('Tournament must have at least one participant');
        }

        $tournamentId = TournamentIdentifier::generate();
        $totalRounds = $this->calculateTotalRounds(count($participants));
        
        // Convert MonsterIdentifiers to TournamentParticipants
        $tournamentParticipants = [];
        foreach ($participants as $monster) {
            $tournamentParticipants[] = new TournamentParticipant($monster);
        }

        return new Tournament($tournamentId, $userEmail, $tournamentParticipants, $totalRounds);
    }

    //! @brief Calculate standings from match results
    //! @param participants Array of participants
    //! @param matchResults Array of match results
    //! @return array<string,int> Standings (monster string => score)
    public function calculateStandings(array $participants, array $matchResults): array
    {
        $standings = [];
        
        // Initialize all participants with 0 score
        foreach ($participants as $participant) {
            $standings[$participant->__toString()] = 0;
        }

        // Process match results
        foreach ($matchResults as $matchResult) {
            [$participant1, $participant2, $outcome] = $matchResult;
            
            switch ($outcome) {
                case 'win':
                    $standings[$participant1->__toString()] += 3;
                    break;
                case 'loss':
                    // Losses don't add points
                    break;
                case 'draw':
                    $standings[$participant1->__toString()] += 1;
                    $standings[$participant2->__toString()] += 1;
                    break;
            }
        }

        return $standings;
    }

    //! @brief Sort participants by standings
    //! @param participants Array of participants
    //! @param standings Current standings (monster => score)
    //! @return array<MonsterIdentifier> Sorted participants
    private function sortParticipantsByStandings(array $participants, array $standings): array
    {
        usort($participants, function (MonsterIdentifier $a, MonsterIdentifier $b) use ($standings) {
            $scoreA = $standings[$a->__toString()] ?? 0;
            $scoreB = $standings[$b->__toString()] ?? 0;
            
            $scoreComparison = $scoreB <=> $scoreA;
            if ($scoreComparison !== 0) {
                return $scoreComparison;
            }
            
            // Tie-breaker: alphabetical order
            return strcmp($a->__toString(), $b->__toString());
        });

        return $participants;
    }

    //! @brief Find the best opponent for a participant
    //! @param participants Array of all participants
    //! @param used Array indicating which participants are already paired
    //! @param participantIndex Index of the participant to find opponent for
    //! @param previousMatchups Previous matchups to avoid
    //! @param standings Current standings
    //! @return int|null Index of best opponent or null if none found
    private function findBestOpponent(
        array $participants,
        array $used,
        int $participantIndex,
        array $previousMatchups,
        array $standings
    ): ?int {
        $participant = $participants[$participantIndex];
        $bestOpponentIndex = null;
        $bestScore = PHP_INT_MAX;

        // For Swiss pairing, we want to pair participants with similar scores
        // Start from the next participant and work forward
        for ($i = $participantIndex + 1; $i < count($participants); $i++) {
            if ($used[$i]) {
                continue;
            }

            $opponent = $participants[$i];
            
            // Skip if they've already played
            if ($this->havePlayedBefore($participant, $opponent, $previousMatchups)) {
                continue;
            }

            // Calculate pairing score (lower is better)
            // For Swiss pairing, we prefer larger score differences (top vs bottom)
            $score = $this->calculatePairingScore($participant, $opponent, $standings);
            
            if ($score < $bestScore) {
                $bestScore = $score;
                $bestOpponentIndex = $i;
            }
        }

        return $bestOpponentIndex;
    }

    //! @brief Check if two participants have played before
    //! @param participant1 First participant
    //! @param participant2 Second participant
    //! @param previousMatchups Previous matchups
    //! @return bool True if they have played before
    private function havePlayedBefore(
        MonsterIdentifier $participant1,
        MonsterIdentifier $participant2,
        array $previousMatchups
    ): bool {
        foreach ($previousMatchups as $matchup) {
            if (count($matchup) === 2) {
                if (($matchup[0]->equals($participant1) && $matchup[1]->equals($participant2)) ||
                    ($matchup[0]->equals($participant2) && $matchup[1]->equals($participant1))) {
                    return true;
                }
            }
        }
        return false;
    }

    //! @brief Calculate pairing score for Swiss pairing
    //! @param participant1 First participant
    //! @param participant2 Second participant
    //! @param standings Current standings
    //! @return int Pairing score (lower is better)
    private function calculatePairingScore(
        MonsterIdentifier $participant1,
        MonsterIdentifier $participant2,
        array $standings
    ): int {
        $score1 = $standings[$participant1->__toString()] ?? 0;
        $score2 = $standings[$participant2->__toString()] ?? 0;
        
        // For Swiss pairing, we prefer similar scores (same score bracket)
        // Lower score difference means better pairing
        return abs($score1 - $score2);
    }
}
