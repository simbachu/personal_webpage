<?php

declare(strict_types=1);

namespace App\Repository;

use App\Type\Tournament;
use App\Type\TournamentIdentifier;
use App\Type\MonsterIdentifier;

//! @brief Interface for tournament persistence operations
//!
//! Defines the contract for persisting tournament data including
//! CRUD operations and user-specific queries.
interface TournamentRepositoryInterface
{
    //! @brief Save a tournament
    //! @param tournament The tournament to save
    public function save(Tournament $tournament): void;

    //! @brief Find a tournament by ID
    //! @param id The tournament identifier
    //! @return Tournament|null The tournament or null if not found
    public function findById(TournamentIdentifier $id): ?Tournament;

    //! @brief Find tournaments by user email
    //! @param userEmail The user's email
    //! @return array<Tournament> Array of tournaments for the user
    public function findByUserEmail(string $userEmail): array;

    //! @brief Delete a tournament
    //! @param id The tournament identifier
    public function delete(TournamentIdentifier $id): void;

    //! @brief Check if a tournament exists
    //! @param id The tournament identifier
    //! @return bool True if the tournament exists
    public function exists(TournamentIdentifier $id): bool;

    //! @brief Get all tournaments
    //! @return array<Tournament> All tournaments
    public function findAll(): array;

    //! @brief Save a match result to the database
    //! @param tournamentId Tournament identifier
    //! @param roundNumber Round number for this match
    //! @param participant1 First participant monster identifier
    //! @param participant2 Second participant monster identifier
    //! @param outcome Match outcome ('win', 'loss', 'draw')
    //! @param winner Winner monster identifier (null for draws)
    public function saveMatch(
        TournamentIdentifier $tournamentId,
        int $roundNumber,
        MonsterIdentifier $participant1,
        MonsterIdentifier $participant2,
        string $outcome,
        ?MonsterIdentifier $winner
    ): void;

    //! @brief Load all matches for a tournament
    //! @param tournamentId Tournament identifier
    //! @return array<array{round:int,participant1:MonsterIdentifier,participant2:MonsterIdentifier,outcome:string,winner:MonsterIdentifier|null}> Array of match data
    public function loadMatches(TournamentIdentifier $tournamentId): array;

    //! @brief Save bracket data for a tournament
    //! @param tournamentId Tournament identifier
    //! @param bracketData Bracket structure (will be JSON encoded)
    public function saveBracketData(TournamentIdentifier $tournamentId, array $bracketData): void;

    //! @brief Load bracket data for a tournament
    //! @param tournamentId Tournament identifier
    //! @return array|null Bracket structure or null if not found
    public function loadBracketData(TournamentIdentifier $tournamentId): ?array;
}
