<?php

declare(strict_types=1);

namespace App\Service;

use App\Type\MonsterIdentifier;
use App\Type\SingleEliminationBracket;
use App\Type\TournamentParticipant;
use InvalidArgumentException;

//! @brief Service for creating top-X brackets from Swiss standings
final class TopBracketService
{
    //! @brief Create a single-elimination bracket from Swiss standings
    //! @param participants All participants
    //! @param standings Monster string => score (Swiss final standings)
    //! @param topN Number to cut to bracket
    public function createTopSingleEliminationBracket(
        array $participants,
        array $standings,
        int $topN
    ): SingleEliminationBracket {
        if ($topN < 2) {
            throw new InvalidArgumentException('topN must be at least 2');
        }

        // Map monster string to TournamentParticipant
        $byId = [];
        foreach ($participants as $p) {
            assert($p instanceof TournamentParticipant);
            $byId[$p->getMonster()->__toString()] = $p;
        }

        // Sort standings by score desc then name asc to get seeds
        uasort($standings, function (int $a, int $b) {
            return $b <=> $a;
        });

        // Build sorted list of monster names by score then name (deterministic)
        $sortedMonsters = array_keys($standings);
        usort($sortedMonsters, function (string $a, string $b) use ($standings) {
            $scoreCmp = ($standings[$b] ?? 0) <=> ($standings[$a] ?? 0);
            if ($scoreCmp !== 0) {
                return $scoreCmp;
            }
            return strcmp($a, $b);
        });

        $topMonsters = array_slice($sortedMonsters, 0, $topN);
        $seeded = [];
        foreach ($topMonsters as $name) {
            if (!isset($byId[$name])) {
                // Create participant if not provided in list
                $byId[$name] = new TournamentParticipant(MonsterIdentifier::fromString($name));
            }
            $seeded[] = $byId[$name];
        }

        return new SingleEliminationBracket($seeded);
    }
}


