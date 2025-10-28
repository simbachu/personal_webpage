<?php

declare(strict_types=1);

namespace App\Repository;

use App\Type\Tournament;
use App\Type\TournamentIdentifier;

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
}
