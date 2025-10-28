<?php

declare(strict_types=1);

namespace App\Service;

use App\Type\MonsterIdentifier;
use App\Type\TournamentParticipant;

//! @brief Interface for Swiss tournament pairing algorithms
//!
//! Defines the contract for generating Swiss-style tournament pairings.
//! Swiss tournaments pair participants based on current standings to ensure
//! balanced competition throughout the tournament.
interface SwissPairingInterface
{
    //! @brief Generate pairings for the next round
    //! @param participants Array of participants to pair
    //! @param previousMatchups Array of previous matchups to avoid repeats
    //! @param standings Current standings (monster string => score)
    //! @return array<array<MonsterIdentifier>> Array of pairings (each pairing is an array of 1-2 participants)
    public function generatePairings(
        array $participants,
        array $previousMatchups = [],
        array $standings = []
    ): array;

    //! @brief Calculate total rounds needed for a Swiss tournament
    //! @param participantCount Number of participants
    //! @return int Number of rounds needed
    public function calculateTotalRounds(int $participantCount): int;

    //! @brief Sort standings by tie-breaker criteria
    //! @param standings Current standings (monster string => score)
    //! @param participants All participants for tie-breaking
    //! @return array<array{monster:MonsterIdentifier,score:int}> Sorted standings with tie-breaker info
    public function sortStandingsByTieBreaker(array $standings, array $participants): array;
}
