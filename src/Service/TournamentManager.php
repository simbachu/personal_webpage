<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\TournamentRepositoryInterface;
use App\Service\DoubleEliminationBracketService;
use App\Type\MonsterIdentifier;
use App\Type\Tournament;
use App\Type\TournamentIdentifier;
use App\Type\TournamentParticipant;
use App\Type\TournamentMatch;
use App\Type\TournamentResult;
use InvalidArgumentException;
use RuntimeException;

//! @brief Tournament manager coordinating repository and service operations
//!
//! Manages tournament lifecycle including creation, match recording,
//! round progression, and standings calculation.
final class TournamentManager implements TournamentManagerInterface
{
    //! @brief Construct tournament manager
    //! @param repository Tournament repository for persistence
    //! @param swissService Swiss tournament service for pairing logic
    //! @param bracketService Double elimination bracket service
    public function __construct(
        private readonly TournamentRepositoryInterface $repository,
        private readonly SwissTournamentService $swissService,
        private readonly DoubleEliminationBracketService $bracketService
    ) {
    }

    //! @brief Create a new tournament
    //! @param participants Array of monster identifiers
    //! @param userEmail Email of the user creating the tournament
    //! @return Tournament The created tournament
    public function createTournament(array $participants, string $userEmail): Tournament
    {
        if (empty($participants)) {
            throw new InvalidArgumentException('Tournament must have at least one participant');
        }

        $tournament = $this->swissService->createTournament($participants, $userEmail);
        $this->repository->save($tournament);
        
        return $tournament;
    }

    //! @brief Get a tournament by ID
    //! @param tournamentId The tournament identifier (string or TournamentIdentifier)
    //! @return Tournament The tournament
    //! @throws \InvalidArgumentException If tournament not found or invalid ID format
    public function getTournament(string|TournamentIdentifier $tournamentId): Tournament
    {
        $id = $tournamentId instanceof TournamentIdentifier 
            ? $tournamentId 
            : TournamentIdentifier::fromString($tournamentId);
        
        $tournament = $this->repository->findById($id);
        if ($tournament === null) {
            throw new InvalidArgumentException("Tournament not found: {$id->__toString()}");
        }
        
        return $tournament;
    }

    //! @brief Get tournaments for a specific user
    //! @param userEmail The user's email
    //! @return array<Tournament> Array of tournaments for the user
    public function getUserTournaments(string $userEmail): array
    {
        return $this->repository->findByUserEmail($userEmail);
    }

    //! @brief Get current round pairings for a tournament
    //! @param tournamentId The tournament identifier (string or TournamentIdentifier)
    //! @return array<array<MonsterIdentifier>> Current round pairings
    public function getCurrentRoundPairings(string|TournamentIdentifier $tournamentId): array
    {
        $id = $tournamentId instanceof TournamentIdentifier 
            ? $tournamentId 
            : TournamentIdentifier::fromString($tournamentId);
        $tournament = $this->getTournament($id);
        
        if ($tournament->isComplete()) {
            return []; // No more pairings for completed tournament
        }

        // Get participants as MonsterIdentifiers
        $participants = array_map(
            fn(TournamentParticipant $p) => $p->getMonster(),
            $tournament->getParticipants()
        );

        // Get previous matchups from all completed rounds
        $previousMatchups = $this->getPreviousMatchups($tournament);
        
        // Get current standings
        $standings = $this->buildStandingsArray($tournament);

        // Generate pairings for current round
        return $this->swissService->generatePairings(
            $participants,
            $previousMatchups,
            $standings
        );
    }

    //! @brief Record a match result
    //! @param tournamentId The tournament identifier (string or TournamentIdentifier)
    //! @param participant1 First participant
    //! @param participant2 Second participant
    //! @param outcome Match outcome ('win', 'loss', 'draw')
    //! @param winner Winning participant (null for draws)
    public function recordMatchResult(
        string|TournamentIdentifier $tournamentId,
        MonsterIdentifier $participant1,
        MonsterIdentifier $participant2,
        string $outcome,
        ?MonsterIdentifier $winner
    ): void {
        $id = $tournamentId instanceof TournamentIdentifier 
            ? $tournamentId 
            : TournamentIdentifier::fromString($tournamentId);
        $tournament = $this->getTournament($id);

        // Find participant objects
        $p1 = $tournament->getParticipant($participant1);
        $p2 = $tournament->getParticipant($participant2);

        if ($p1 === null || $p2 === null) {
            throw new InvalidArgumentException(
                "One or both participants not found in tournament: {$participant1->__toString()}, {$participant2->__toString()}"
            );
        }

        // Validate outcome
        if (!in_array($outcome, ['win', 'loss', 'draw'], true)) {
            throw new InvalidArgumentException("Invalid outcome: $outcome. Must be 'win', 'loss', or 'draw'");
        }

        // Determine winner participant for result
        $winnerParticipant = null;
        if ($outcome === 'draw') {
            if ($winner !== null) {
                throw new InvalidArgumentException('Winner must be null for draw outcomes');
            }
        } else {
            if ($winner === null) {
                throw new InvalidArgumentException('Winner cannot be null for win/loss outcomes');
            }
            
            if ($winner->equals($participant1)) {
                $winnerParticipant = $p1;
            } elseif ($winner->equals($participant2)) {
                $winnerParticipant = $p2;
            } else {
                throw new InvalidArgumentException(
                    'Winner must be one of the match participants'
                );
            }
        }

        // Create match and record result
        $match = new TournamentMatch($p1, $p2, $tournament->getCurrentRound());
        $result = new TournamentResult($outcome, $winnerParticipant);
        $match->recordResult($result);

        // Save match to database
        $this->repository->saveMatch(
            $id,
            $tournament->getCurrentRound(),
            $participant1,
            $participant2,
            $outcome,
            $winner
        );

        // Save tournament with updated participant stats
        $this->repository->save($tournament);
    }

    //! @brief Record a bye (free win) for a participant in the current round
    //! @param tournamentId The tournament identifier (string or TournamentIdentifier)
    //! @param participant Participant who received the bye
    public function recordBye(
        string|TournamentIdentifier $tournamentId,
        MonsterIdentifier $participant
    ): void {
        $id = $tournamentId instanceof TournamentIdentifier 
            ? $tournamentId 
            : TournamentIdentifier::fromString($tournamentId);
        $tournament = $this->getTournament($id);

        // Find participant object
        $p = $tournament->getParticipant($participant);
        if ($p === null) {
            throw new InvalidArgumentException(
                "Participant not found in tournament: {$participant->__toString()}"
            );
        }

        // Add a win for the bye (3 points)
        $p->addWin();

        // Save tournament with updated participant stats
        $this->repository->save($tournament);
    }

    //! @brief Check if all matches in the current round are complete
    //! @param tournamentId The tournament identifier (string or TournamentIdentifier)
    //! @return bool True if all matches in current round are complete
    public function isCurrentRoundComplete(string|TournamentIdentifier $tournamentId): bool
    {
        $id = $tournamentId instanceof TournamentIdentifier 
            ? $tournamentId 
            : TournamentIdentifier::fromString($tournamentId);
        $tournament = $this->getTournament($id);
        
        if ($tournament->isComplete()) {
            return true;
        }

        $pairings = $this->getCurrentRoundPairings($tournamentId);
        if (empty($pairings)) {
            return true; // No pairings means round is complete
        }

        // Load matches for current round
        $matches = $this->repository->loadMatches($id);
        $currentRoundMatches = array_filter(
            $matches,
            fn($match) => $match['round'] === $tournament->getCurrentRound()
        );

        // Create a set of completed matchups for current round
        $completedMatchups = [];
        foreach ($currentRoundMatches as $match) {
            $p1Str = $match['participant1']->__toString();
            $p2Str = $match['participant2']->__toString();
            // Normalize: always store in consistent order
            if ($p1Str > $p2Str) {
                [$p1Str, $p2Str] = [$p2Str, $p1Str];
            }
            $completedMatchups["{$p1Str}:{$p2Str}"] = true;
        }

        // Check if all pairings have completed matches
        foreach ($pairings as $pairing) {
            if (count($pairing) === 1) {
                continue; // Bye doesn't need a match
            }
            
            $p1Str = $pairing[0]->__toString();
            $p2Str = $pairing[1]->__toString();
            if ($p1Str > $p2Str) {
                [$p1Str, $p2Str] = [$p2Str, $p1Str];
            }
            
            $matchupKey = "{$p1Str}:{$p2Str}";
            if (!isset($completedMatchups[$matchupKey])) {
                return false; // Found an incomplete match
            }
        }

        return true;
    }

    //! @brief Advance tournament to next round
    //! @param tournamentId The tournament identifier (string or TournamentIdentifier)
    public function advanceToNextRound(string|TournamentIdentifier $tournamentId): void
    {
        $id = $tournamentId instanceof TournamentIdentifier 
            ? $tournamentId 
            : TournamentIdentifier::fromString($tournamentId);
        $tournament = $this->getTournament($id);
        
        if ($tournament->isComplete()) {
            throw new RuntimeException('Cannot advance round: tournament is already complete');
        }

        // Check if all matches in current round are complete
        if (!$this->isCurrentRoundComplete($tournamentId)) {
            throw new RuntimeException('Cannot advance round: not all matches in current round are complete');
        }
        
        $tournament->advanceRound();
        $this->repository->save($tournament);

        // If Swiss rounds are complete, initialize bracket
        if ($tournament->isComplete()) {
            $this->initializeBracket($id);
        }
    }

    //! @brief Initialize double elimination bracket from top 16
    //! @param tournamentId Tournament identifier
    public function initializeBracket(string|TournamentIdentifier $tournamentId): void
    {
        $id = $tournamentId instanceof TournamentIdentifier 
            ? $tournamentId 
            : TournamentIdentifier::fromString($tournamentId);
        
        // Check if bracket already exists
        $existingBracket = $this->repository->loadBracketData($id);
        if ($existingBracket !== null) {
            return; // Bracket already initialized
        }

        $tournament = $this->getTournament($id);
        if (!$tournament->isComplete()) {
            throw new RuntimeException('Cannot initialize bracket: Swiss rounds not complete');
        }

        // Get top 16 from final standings
        $standings = $this->getFinalStandings($id);
        $top16 = array_slice($standings, 0, 16);
        
        if (count($top16) < 16) {
            throw new RuntimeException('Cannot initialize bracket: Need at least 16 participants');
        }

        // Extract monster identifiers in seed order
        $participants = array_map(
            fn($standing) => $standing['monster'],
            $top16
        );

        // Create bracket structure
        $bracket = $this->bracketService->createBracket($participants);

        // Convert MonsterIdentifier objects to strings for JSON storage
        $bracket = $this->serializeBracket($bracket);

        // Save bracket
        $this->repository->saveBracketData($id, $bracket);
    }

    //! @brief Get bracket data for a tournament
    //! @param tournamentId Tournament identifier
    //! @return array|null Bracket structure or null if not initialized
    public function getBracket(string|TournamentIdentifier $tournamentId): ?array
    {
        $id = $tournamentId instanceof TournamentIdentifier 
            ? $tournamentId 
            : TournamentIdentifier::fromString($tournamentId);
        
        $bracket = $this->repository->loadBracketData($id);
        if ($bracket === null) {
            return null;
        }

        // Deserialize MonsterIdentifier objects
        return $this->deserializeBracket($bracket);
    }

    //! @brief Record a bracket match result
    //! @param tournamentId Tournament identifier
    //! @param matchId Match identifier
    //! @param winner Winning participant
    public function recordBracketMatchResult(
        string|TournamentIdentifier $tournamentId,
        string $matchId,
        MonsterIdentifier $winner
    ): void {
        $id = $tournamentId instanceof TournamentIdentifier 
            ? $tournamentId 
            : TournamentIdentifier::fromString($tournamentId);
        
        $bracket = $this->repository->loadBracketData($id);
        if ($bracket === null) {
            throw new RuntimeException('Bracket not initialized for tournament');
        }

        // Deserialize for processing
        $bracket = $this->deserializeBracket($bracket);

        // Record result
        $this->bracketService->recordMatchResult($bracket, $matchId, $winner);

        // Serialize and save
        $bracket = $this->serializeBracket($bracket);
        $this->repository->saveBracketData($id, $bracket);
    }

    //! @brief Get next bracket match ready for voting
    //! @param tournamentId Tournament identifier
    //! @return array|null Match data or null if no matches ready
    public function getNextBracketMatch(string|TournamentIdentifier $tournamentId): ?array
    {
        $id = $tournamentId instanceof TournamentIdentifier 
            ? $tournamentId 
            : TournamentIdentifier::fromString($tournamentId);
        
        $bracket = $this->getBracket($id);
        if ($bracket === null) {
            return null;
        }

        $matches = $this->bracketService->getMatchesReadyForVoting($bracket);
        return $matches[0] ?? null;
    }

    //! @brief Check if bracket is complete
    //! @param tournamentId Tournament identifier
    //! @return bool True if bracket is complete
    public function isBracketComplete(string|TournamentIdentifier $tournamentId): bool
    {
        $id = $tournamentId instanceof TournamentIdentifier 
            ? $tournamentId 
            : TournamentIdentifier::fromString($tournamentId);
        
        $bracket = $this->getBracket($id);
        if ($bracket === null) {
            return false;
        }

        return $this->bracketService->isBracketComplete($bracket);
    }

    //! @brief Serialize bracket for storage (MonsterIdentifier -> string)
    //! @param bracket Bracket structure with MonsterIdentifier objects
    //! @return array Bracket structure with string identifiers
    private function serializeBracket(array $bracket): array
    {
        $serialized = $bracket;
        
        // Serialize winner bracket
        foreach (['round1', 'round2', 'round3', 'round4'] as $round) {
            if (!isset($serialized['winner_bracket'][$round])) {
                continue;
            }
            foreach ($serialized['winner_bracket'][$round] as $idx => $match) {
                $serialized['winner_bracket'][$round][$idx]['participant1'] = $match['participant1'] instanceof MonsterIdentifier 
                    ? $match['participant1']->__toString() 
                    : $match['participant1'];
                $serialized['winner_bracket'][$round][$idx]['participant2'] = $match['participant2'] instanceof MonsterIdentifier 
                    ? $match['participant2']->__toString() 
                    : $match['participant2'];
                $serialized['winner_bracket'][$round][$idx]['winner'] = $match['winner'] instanceof MonsterIdentifier 
                    ? $match['winner']->__toString() 
                    : ($match['winner'] ?? null);
            }
        }

        // Serialize loser bracket
        foreach (['round1', 'round2', 'round3', 'round4', 'round5'] as $round) {
            if (!isset($serialized['loser_bracket'][$round])) {
                continue;
            }
            foreach ($serialized['loser_bracket'][$round] as $idx => $match) {
                $serialized['loser_bracket'][$round][$idx]['participant1'] = $match['participant1'] instanceof MonsterIdentifier 
                    ? $match['participant1']->__toString() 
                    : $match['participant1'];
                $serialized['loser_bracket'][$round][$idx]['participant2'] = $match['participant2'] instanceof MonsterIdentifier 
                    ? $match['participant2']->__toString() 
                    : $match['participant2'];
                $serialized['loser_bracket'][$round][$idx]['winner'] = $match['winner'] instanceof MonsterIdentifier 
                    ? $match['winner']->__toString() 
                    : ($match['winner'] ?? null);
            }
        }

        // Serialize grand finals
        if (isset($serialized['grand_finals'])) {
            foreach ($serialized['grand_finals'] as $idx => $match) {
                $serialized['grand_finals'][$idx]['participant1'] = $match['participant1'] instanceof MonsterIdentifier 
                    ? $match['participant1']->__toString() 
                    : $match['participant1'];
                $serialized['grand_finals'][$idx]['participant2'] = $match['participant2'] instanceof MonsterIdentifier 
                    ? $match['participant2']->__toString() 
                    : ($match['participant2'] ?? null);
                $serialized['grand_finals'][$idx]['winner'] = $match['winner'] instanceof MonsterIdentifier 
                    ? $match['winner']->__toString() 
                    : ($match['winner'] ?? null);
            }
        }

        return $serialized;
    }

    //! @brief Deserialize bracket from storage (string -> MonsterIdentifier)
    //! @param bracket Bracket structure with string identifiers
    //! @return array Bracket structure with MonsterIdentifier objects
    private function deserializeBracket(array $bracket): array
    {
        $deserialized = $bracket;
        
        // Deserialize winner bracket
        foreach (['round1', 'round2', 'round3', 'round4'] as $round) {
            if (!isset($deserialized['winner_bracket'][$round])) {
                continue;
            }
            foreach ($deserialized['winner_bracket'][$round] as $idx => $match) {
                $deserialized['winner_bracket'][$round][$idx]['participant1'] = is_string($match['participant1'])
                    ? MonsterIdentifier::fromString($match['participant1'])
                    : $match['participant1'];
                $deserialized['winner_bracket'][$round][$idx]['participant2'] = is_string($match['participant2'])
                    ? MonsterIdentifier::fromString($match['participant2'])
                    : ($match['participant2'] ?? null);
                $deserialized['winner_bracket'][$round][$idx]['winner'] = is_string($match['winner'] ?? null)
                    ? MonsterIdentifier::fromString($match['winner'])
                    : ($match['winner'] ?? null);
            }
        }

        // Deserialize loser bracket
        foreach (['round1', 'round2', 'round3', 'round4', 'round5'] as $round) {
            if (!isset($deserialized['loser_bracket'][$round])) {
                continue;
            }
            foreach ($deserialized['loser_bracket'][$round] as $idx => $match) {
                $deserialized['loser_bracket'][$round][$idx]['participant1'] = is_string($match['participant1'])
                    ? MonsterIdentifier::fromString($match['participant1'])
                    : $match['participant1'];
                $deserialized['loser_bracket'][$round][$idx]['participant2'] = is_string($match['participant2'])
                    ? MonsterIdentifier::fromString($match['participant2'])
                    : ($match['participant2'] ?? null);
                $deserialized['loser_bracket'][$round][$idx]['winner'] = is_string($match['winner'] ?? null)
                    ? MonsterIdentifier::fromString($match['winner'])
                    : ($match['winner'] ?? null);
            }
        }

        // Deserialize grand finals
        if (isset($deserialized['grand_finals'])) {
            foreach ($deserialized['grand_finals'] as $idx => $match) {
                $deserialized['grand_finals'][$idx]['participant1'] = is_string($match['participant1'])
                    ? MonsterIdentifier::fromString($match['participant1'])
                    : $match['participant1'];
                $deserialized['grand_finals'][$idx]['participant2'] = is_string($match['participant2'] ?? null)
                    ? MonsterIdentifier::fromString($match['participant2'])
                    : ($match['participant2'] ?? null);
                $deserialized['grand_finals'][$idx]['winner'] = is_string($match['winner'] ?? null)
                    ? MonsterIdentifier::fromString($match['winner'])
                    : ($match['winner'] ?? null);
            }
        }

        return $deserialized;
    }

    //! @brief Get current standings for a tournament
    //! @param tournamentId The tournament identifier (string or TournamentIdentifier)
    //! @return array<array{monster:MonsterIdentifier,score:int,wins:int,losses:int,draws:int}> Current standings
    public function getCurrentStandings(string|TournamentIdentifier $tournamentId): array
    {
        $id = $tournamentId instanceof TournamentIdentifier 
            ? $tournamentId 
            : TournamentIdentifier::fromString($tournamentId);
        $tournament = $this->getTournament($id);
        $standings = $tournament->getStandings();
        
        return array_map(function (TournamentParticipant $participant) {
            return [
                'monster' => $participant->getMonster(),
                'score' => $participant->getScore(),
                'wins' => $participant->getWins(),
                'losses' => $participant->getLosses(),
                'draws' => $participant->getDraws(),
            ];
        }, $standings);
    }

    //! @brief Get final standings for a completed tournament
    //! @param tournamentId The tournament identifier (string or TournamentIdentifier)
    //! @return array<array{monster:MonsterIdentifier,score:int,wins:int,losses:int,draws:int}> Final standings
    public function getFinalStandings(string|TournamentIdentifier $tournamentId): array
    {
        $id = $tournamentId instanceof TournamentIdentifier 
            ? $tournamentId 
            : TournamentIdentifier::fromString($tournamentId);
        $tournament = $this->getTournament($id);
        
        if (!$tournament->isComplete()) {
            throw new RuntimeException('Tournament is not complete yet');
        }

        return $this->getCurrentStandings($tournamentId);
    }

    //! @brief Delete a tournament
    //! @param tournamentId The tournament identifier (string or TournamentIdentifier)
    public function deleteTournament(string|TournamentIdentifier $tournamentId): void
    {
        $id = $tournamentId instanceof TournamentIdentifier 
            ? $tournamentId 
            : TournamentIdentifier::fromString($tournamentId);
        
        if (!$this->repository->exists($id)) {
            throw new InvalidArgumentException("Tournament not found: {$id->__toString()}");
        }

        $this->repository->delete($id);
    }

    //! @brief Get previous matchups from tournament history
    //! @param tournament The tournament
    //! @return array<array<MonsterIdentifier>> Array of previous matchups
    private function getPreviousMatchups(Tournament $tournament): array
    {
        $matches = $this->repository->loadMatches($tournament->getId());
        $matchups = [];
        
        foreach ($matches as $match) {
            // Only include completed matches (they all have outcomes)
            $matchups[] = [$match['participant1'], $match['participant2']];
        }
        
        return $matchups;
    }

    //! @brief Build standings array from tournament participants
    //! @param tournament The tournament
    //! @return array<string,int> Standings (monster string => score)
    private function buildStandingsArray(Tournament $tournament): array
    {
        $standings = [];
        foreach ($tournament->getParticipants() as $participant) {
            $standings[$participant->getMonster()->__toString()] = $participant->getScore();
        }
        return $standings;
    }
}

