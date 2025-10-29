<?php

declare(strict_types=1);

namespace App\Service;

use App\Type\MonsterIdentifier;
use App\Type\TournamentParticipant;
use App\Type\SingleEliminationBracket;
use App\Type\DoubleEliminationBracket;

//! @brief Factory for creating playoff brackets from Swiss standings
final class PlayoffBracketFactory
{
    //! @param participants All participants as TournamentParticipant[] or MonsterIdentifier[]
    //! @param standings Monster string => score
    //! @return object SingleEliminationBracket|DoubleEliminationBracket
    public function createFromStandings(
        array $participants,
        array $standings,
        int $topN,
        string $playoffType,
        bool $reset
    ): object {
        $seeded = $this->seedTopN($participants, $standings, $topN);
        if ($playoffType === 'single-elimination') {
            return new SingleEliminationBracket($seeded);
        }
        return new DoubleEliminationBracket($seeded, $reset);
    }

    /** @return array<int,TournamentParticipant> */
    private function seedTopN(array $participants, array $standings, int $topN): array
    {
        // Normalize to TournamentParticipant[]
        $byId = [];
        foreach ($participants as $p) {
            if ($p instanceof TournamentParticipant) {
                $byId[$p->getMonster()->__toString()] = $p;
            } elseif ($p instanceof MonsterIdentifier) {
                $tp = new TournamentParticipant($p);
                $byId[$p->__toString()] = $tp;
            }
        }

        // Sort by score DESC, then name ASC
        $names = array_keys($standings);
        usort($names, function (string $a, string $b) use ($standings) {
            $scoreCmp = ($standings[$b] ?? 0) <=> ($standings[$a] ?? 0);
            if ($scoreCmp !== 0) {
                return $scoreCmp;
            }
            return strcmp($a, $b);
        });

        $seeded = [];
        foreach (array_slice($names, 0, $topN) as $name) {
            if (!isset($byId[$name])) {
                $byId[$name] = new TournamentParticipant(MonsterIdentifier::fromString($name));
            }
            $seeded[] = $byId[$name];
        }
        return $seeded;
    }
}


