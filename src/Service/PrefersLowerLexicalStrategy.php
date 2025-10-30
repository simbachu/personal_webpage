<?php

declare(strict_types=1);

namespace App\Service;

use App\Type\MonsterIdentifier;

//! @brief Strategy that prefers Pokemon with alphabetically earlier names
//!
//! This strategy always picks the Pokemon whose name comes first
//! lexicographically. For example, "Abomasnow" beats "Zorua".
//!
//! @code
//! // Example usage:
//! $strategy = new PrefersLowerLexicalStrategy();
//! $winner = $strategy->chooseWinner(
//!     MonsterIdentifier::fromString('charmander'),
//!     MonsterIdentifier::fromString('bulbasaur')
//! );
//! // $winner is bulbasaur (comes before charmander alphabetically)
//! @endcode
final class PrefersLowerLexicalStrategy implements TournamentStrategyInterface
{
    //! @brief Choose the winner based on lexicographic ordering
    //! @param participant1 First participant
    //! @param participant2 Second participant
    //! @return MonsterIdentifier The participant with the lower lexicographic name
    public function chooseWinner(MonsterIdentifier $participant1, MonsterIdentifier $participant2): MonsterIdentifier
    {
        $name1 = strtolower($participant1->__toString());
        $name2 = strtolower($participant2->__toString());
        
        return ($name1 <= $name2) ? $participant1 : $participant2;
    }
}

