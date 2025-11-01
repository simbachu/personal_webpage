<?php

declare(strict_types=1);

namespace App\Presenter;

use App\Service\TournamentManagerInterface;
use App\Service\PokeApiService;
use App\Type\MonsterIdentifier;
use App\Type\TournamentIdentifier;
use App\Type\TournamentParticipant;
use RuntimeException;

//! @brief Presenter for tournament views
//!
//! Prepares tournament data for templates, fetching Pokemon details
//! and formatting tournament state for display.
class TournamentPresenter
{
    private TournamentManagerInterface $tournamentManager;
    private PokeApiService $pokeApiService;

    //! @brief Construct tournament presenter
    //! @param tournamentManager Tournament manager service
    //! @param pokeApiService PokeAPI service for fetching Pokemon details
    public function __construct(
        TournamentManagerInterface $tournamentManager,
        PokeApiService $pokeApiService
    ) {
        $this->tournamentManager = $tournamentManager;
        $this->pokeApiService = $pokeApiService;
    }

    //! @brief Present voting/match view with two Pokemon
    //! @param tournamentId Tournament identifier
    //! @param participant1 First participant monster identifier
    //! @param participant2 Second participant monster identifier (null for bye)
    //! @return array{monster1:array,monster2:array|null,tournament_id:string,round:int,total_rounds:int}
    public function presentVoting(
        TournamentIdentifier $tournamentId,
        MonsterIdentifier $participant1,
        ?MonsterIdentifier $participant2
    ): array {
        $tournament = $this->tournamentManager->getTournament($tournamentId);
        
        // Fetch Pokemon data for both participants
        $monster1Result = $this->pokeApiService->fetchMonster($participant1);
        if ($monster1Result->isFailure()) {
            throw new RuntimeException("Failed to fetch Pokemon: {$participant1->__toString()}");
        }
        $monster1 = $monster1Result->getValue()->toArray();

        $monster2 = null;
        if ($participant2 !== null) {
            $monster2Result = $this->pokeApiService->fetchMonster($participant2);
            if ($monster2Result->isFailure()) {
                throw new RuntimeException("Failed to fetch Pokemon: {$participant2->__toString()}");
            }
            $monster2 = $monster2Result->getValue()->toArray();
        }

        return [
            'monster1' => $monster1,
            'monster2' => $monster2,
            'tournament_id' => $tournamentId->__toString(),
            'round' => $tournament->getCurrentRound() + 1, // Display as 1-based
            'total_rounds' => $tournament->getTotalRounds(),
        ];
    }

    //! @brief Present tournament progress/standings view
    //! @param tournamentId Tournament identifier
    //! @return array{tournament_id:string,round:int,total_rounds:int,is_complete:bool,standings:array,pairings:array}
    public function presentProgress(TournamentIdentifier $tournamentId): array
    {
        $tournament = $this->tournamentManager->getTournament($tournamentId);
        $standings = $this->tournamentManager->getCurrentStandings($tournamentId);
        $pairings = $this->tournamentManager->getCurrentRoundPairings($tournamentId);

        // Format standings with Pokemon details
        $formattedStandings = [];
        foreach ($standings as $standing) {
            $monsterResult = $this->pokeApiService->fetchMonster($standing['monster']);
            if ($monsterResult->isFailure()) {
                // Fallback: use identifier as name
                $formattedStandings[] = [
                    'name' => $standing['monster']->__toString(),
                    'image' => '',
                    'score' => $standing['score'],
                    'wins' => $standing['wins'],
                    'losses' => $standing['losses'],
                    'draws' => $standing['draws'],
                ];
            } else {
                $monsterData = $monsterResult->getValue()->toArray();
                $formattedStandings[] = [
                    'name' => $monsterData['name'],
                    'image' => $monsterData['image'] ?? '',
                    'score' => $standing['score'],
                    'wins' => $standing['wins'],
                    'losses' => $standing['losses'],
                    'draws' => $standing['draws'],
                ];
            }
        }

        // Format pairings with Pokemon details
        $formattedPairings = [];
        foreach ($pairings as $pairing) {
            $pair = [];
            foreach ($pairing as $monsterId) {
                $monsterResult = $this->pokeApiService->fetchMonster($monsterId);
                if ($monsterResult->isFailure()) {
                    $pair[] = [
                        'name' => $monsterId->__toString(),
                        'image' => '',
                    ];
                } else {
                    $monsterData = $monsterResult->getValue()->toArray();
                    $pair[] = [
                        'name' => $monsterData['name'],
                        'image' => $monsterData['image'] ?? '',
                    ];
                }
            }
            $formattedPairings[] = $pair;
        }

        return [
            'tournament_id' => $tournamentId->__toString(),
            'round' => $tournament->getCurrentRound() + 1, // Display as 1-based
            'total_rounds' => $tournament->getTotalRounds(),
            'is_complete' => $tournament->isComplete(),
            'standings' => $formattedStandings,
            'pairings' => $formattedPairings,
        ];
    }

    //! @brief Present tournament bracket view (double elimination)
    //! @param tournamentId Tournament identifier
    //! @return array{tournament_id:string,bracket:array,winner_bracket:array,loser_bracket:array,grand_finals:array,is_complete:bool}
    public function presentBracket(TournamentIdentifier $tournamentId): array
    {
        $tournament = $this->tournamentManager->getTournament($tournamentId);
        
        if (!$tournament->isComplete()) {
            throw new RuntimeException('Bracket view only available for completed Swiss rounds');
        }

        // Get bracket data
        $bracket = $this->tournamentManager->getBracket($tournamentId);
        
        // If bracket not initialized yet, show top 16 seeds
        if ($bracket === null) {
            $standings = $this->tournamentManager->getFinalStandings($tournamentId);
            $top16 = array_slice($standings, 0, 16);
            
            $formattedTop16 = [];
            foreach ($top16 as $standing) {
                $monsterResult = $this->pokeApiService->fetchMonster($standing['monster']);
                if ($monsterResult->isFailure()) {
                    $formattedTop16[] = [
                        'name' => $standing['monster']->__toString(),
                        'image' => '',
                        'score' => $standing['score'],
                    ];
                } else {
                    $monsterData = $monsterResult->getValue()->toArray();
                    $formattedTop16[] = [
                        'name' => $monsterData['name'],
                        'image' => $monsterData['image'] ?? '',
                        'score' => $standing['score'],
                    ];
                }
            }

            return [
                'tournament_id' => $tournamentId->__toString(),
                'bracket' => $formattedTop16,
                'is_initialized' => false,
                'is_complete' => false,
            ];
        }

        // Format bracket with Pokemon details
        $formattedBracket = $this->formatBracketStructure($bracket);

        return [
            'tournament_id' => $tournamentId->__toString(),
            'winner_bracket' => $formattedBracket['winner_bracket'],
            'loser_bracket' => $formattedBracket['loser_bracket'],
            'grand_finals' => $formattedBracket['grand_finals'],
            'is_initialized' => true,
            'is_complete' => $this->tournamentManager->isBracketComplete($tournamentId),
        ];
    }

    //! @brief Format bracket structure with Pokemon details
    //! @param bracket Raw bracket structure
    //! @return array Formatted bracket with Pokemon details
    private function formatBracketStructure(array $bracket): array
    {
        $formatted = [
            'winner_bracket' => [],
            'loser_bracket' => [],
            'grand_finals' => [],
        ];

        // Format winner bracket
        foreach (['round1', 'round2', 'round3', 'round4'] as $round) {
            if (!isset($bracket['winner_bracket'][$round])) {
                continue;
            }
            $formatted['winner_bracket'][$round] = [];
            foreach ($bracket['winner_bracket'][$round] as $match) {
                $formatted['winner_bracket'][$round][] = $this->formatMatch($match);
            }
        }

        // Format loser bracket
        foreach (['round1', 'round2', 'round3', 'round4', 'round5'] as $round) {
            if (!isset($bracket['loser_bracket'][$round])) {
                continue;
            }
            $formatted['loser_bracket'][$round] = [];
            foreach ($bracket['loser_bracket'][$round] as $match) {
                $formatted['loser_bracket'][$round][] = $this->formatMatch($match);
            }
        }

        // Format grand finals
        if (isset($bracket['grand_finals'])) {
            foreach ($bracket['grand_finals'] as $match) {
                $formatted['grand_finals'][] = $this->formatMatch($match);
            }
        }

        return $formatted;
    }

    //! @brief Format a single match with Pokemon details
    //! @param match Match data
    //! @return array Formatted match
    private function formatMatch(array $match): array
    {
        $formatted = [
            'id' => $match['id'],
            'round' => $match['round'],
            'participant1' => null,
            'participant2' => null,
            'winner' => null,
        ];

        // Format participant1
        if ($match['participant1'] !== null) {
            $monsterResult = $this->pokeApiService->fetchMonster($match['participant1']);
            if ($monsterResult->isFailure()) {
                $formatted['participant1'] = [
                    'name' => $match['participant1']->__toString(),
                    'image' => '',
                ];
            } else {
                $monsterData = $monsterResult->getValue()->toArray();
                $formatted['participant1'] = [
                    'name' => $monsterData['name'],
                    'image' => $monsterData['image'] ?? '',
                ];
            }
        }

        // Format participant2
        if ($match['participant2'] !== null) {
            $monsterResult = $this->pokeApiService->fetchMonster($match['participant2']);
            if ($monsterResult->isFailure()) {
                $formatted['participant2'] = [
                    'name' => $match['participant2']->__toString(),
                    'image' => '',
                ];
            } else {
                $monsterData = $monsterResult->getValue()->toArray();
                $formatted['participant2'] = [
                    'name' => $monsterData['name'],
                    'image' => $monsterData['image'] ?? '',
                ];
            }
        }

        // Format winner
        if ($match['winner'] !== null) {
            $monsterResult = $this->pokeApiService->fetchMonster($match['winner']);
            if ($monsterResult->isFailure()) {
                $formatted['winner'] = [
                    'name' => $match['winner']->__toString(),
                    'image' => '',
                ];
            } else {
                $monsterData = $monsterResult->getValue()->toArray();
                $formatted['winner'] = [
                    'name' => $monsterData['name'],
                    'image' => $monsterData['image'] ?? '',
                ];
            }
        }

        return $formatted;
    }

    //! @brief Present tournament setup/landing view
    //! @return array{} Empty array for now (can be extended with tournament list, etc.)
    public function presentSetup(): array
    {
        return [];
    }
}

