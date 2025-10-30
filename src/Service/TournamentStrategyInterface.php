<?php

declare(strict_types=1);

namespace App\Service;

use App\Type\MonsterIdentifier;

//! @brief Interface for tournament strategies that decide match winners
//!
//! Implementations of this interface make deterministic or heuristic-based
//! choices when deciding between two Pokemon in a tournament match.
//!
//! @code
//! // Example usage:
//! $strategy = new PrefersLowerLexicalStrategy();
//! $winner = $strategy->chooseWinner($participant1, $participant2);
//! // $winner is either $participant1 or $participant2
//! @endcode
interface TournamentStrategyInterface
{
    //! @brief Choose the winner between two participants in a match
    //! @param participant1 First participant
    //! @param participant2 Second participant
    //! @return MonsterIdentifier The chosen winner
    public function chooseWinner(MonsterIdentifier $participant1, MonsterIdentifier $participant2): MonsterIdentifier;
}

