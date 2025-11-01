<?php

declare(strict_types=1);

namespace App\Service;

use App\Type\MonsterIdentifier;
use App\Type\Tournament;
use App\Type\TournamentIdentifier;

//! @brief Interface for tournament management operations
//!
//! Defines the contract for managing tournament lifecycle including
//! creation, progression, and result recording.
interface TournamentManagerInterface
{
    //! @brief Create a new tournament
    //! @param participants Array of monster identifiers
    //! @param userEmail Email of the user creating the tournament
    //! @return Tournament The created tournament
    public function createTournament(array $participants, string $userEmail): Tournament;

    //! @brief Get a tournament by ID
    //! @param tournamentId The tournament identifier
    //! @return Tournament The tournament
    //! @throws \InvalidArgumentException If tournament not found
    public function getTournament(TournamentIdentifier $tournamentId): Tournament;

    //! @brief Get tournaments for a specific user
    //! @param userEmail The user's email
    //! @return array<Tournament> Array of tournaments for the user
    public function getUserTournaments(string $userEmail): array;

    //! @brief Get current round pairings for a tournament
    //! @param tournamentId The tournament identifier
    //! @return array<array<MonsterIdentifier>> Current round pairings
    public function getCurrentRoundPairings(TournamentIdentifier $tournamentId): array;

    //! @brief Record a match result
    //! @param tournamentId The tournament identifier
    //! @param participant1 First participant
    //! @param participant2 Second participant
    //! @param outcome Match outcome ('win', 'loss', 'draw')
    //! @param winner Winning participant (null for draws)
    public function recordMatchResult(
        TournamentIdentifier $tournamentId,
        MonsterIdentifier $participant1,
        MonsterIdentifier $participant2,
        string $outcome,
        ?MonsterIdentifier $winner
    ): void;

    //! @brief Check if all matches in the current round are complete
    //! @param tournamentId The tournament identifier
    //! @return bool True if all matches in current round are complete
    public function isCurrentRoundComplete(TournamentIdentifier $tournamentId): bool;

    //! @brief Advance tournament to next round
    //! @param tournamentId The tournament identifier
    public function advanceToNextRound(TournamentIdentifier $tournamentId): void;

    //! @brief Get current standings for a tournament
    //! @param tournamentId The tournament identifier
    //! @return array<array{monster:MonsterIdentifier,score:int,wins:int,losses:int,draws:int}> Current standings
    public function getCurrentStandings(TournamentIdentifier $tournamentId): array;

    //! @brief Get final standings for a completed tournament
    //! @param tournamentId The tournament identifier
    //! @return array<array{monster:MonsterIdentifier,score:int,wins:int,losses:int,draws:int}> Final standings
    public function getFinalStandings(TournamentIdentifier $tournamentId): array;

    //! @brief Delete a tournament
    //! @param tournamentId The tournament identifier
    public function deleteTournament(TournamentIdentifier $tournamentId): void;
}
