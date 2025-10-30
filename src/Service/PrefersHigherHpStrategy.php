<?php

declare(strict_types=1);

namespace App\Service;

use App\Type\MonsterIdentifier;

//! @brief Strategy that prefers Pokemon with higher base HP
//!
//! This strategy picks the Pokemon with the higher base HP stat.
//! If both have the same HP, it picks the one with the lower lexicographic name.
//!
//! @code
//! // Example usage:
//! $hpLookup = fn($name) => ['charmander' => 39, 'bulbasaur' => 45][$name] ?? 0;
//! $strategy = new PrefersHigherHpStrategy($hpLookup);
//! $winner = $strategy->chooseWinner(
//!     MonsterIdentifier::fromString('charmander'),
//!     MonsterIdentifier::fromString('bulbasaur')
//! );
//! // $winner is bulbasaur (HP 45 > 39)
//! @endcode
final class PrefersHigherHpStrategy implements TournamentStrategyInterface
{
    /** @var callable(MonsterIdentifier):int */
    private $hpLookup;

    //! @brief Construct a new strategy with HP lookup function
    //! @param hpLookup Callable that returns base HP for a MonsterIdentifier
    public function __construct(callable $hpLookup)
    {
        $this->hpLookup = $hpLookup;
    }

    //! @brief Choose the winner based on higher HP
    //! @param participant1 First participant
    //! @param participant2 Second participant
    //! @return MonsterIdentifier The participant with higher HP (or lower lexicographic name if tie)
    public function chooseWinner(MonsterIdentifier $participant1, MonsterIdentifier $participant2): MonsterIdentifier
    {
        $hp1 = ($this->hpLookup)($participant1);
        $hp2 = ($this->hpLookup)($participant2);
        
        if ($hp1 > $hp2) {
            return $participant1;
        }
        if ($hp2 > $hp1) {
            return $participant2;
        }
        
        // Tie-breaker: prefer lower lexicographic name
        $name1 = strtolower($participant1->__toString());
        $name2 = strtolower($participant2->__toString());
        return ($name1 <= $name2) ? $participant1 : $participant2;
    }
}

