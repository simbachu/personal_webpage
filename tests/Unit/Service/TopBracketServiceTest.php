<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Service\TopBracketService;
use App\Type\TournamentParticipant;
use App\Type\MonsterIdentifier;

final class TopBracketServiceTest extends TestCase
{
    //! @brief Top-X selection from Swiss standings and bracket creation
    public function test_create_bracket_from_standings_top4(): void
    {
        //! @section Arrange
        $participants = [
            new TournamentParticipant(MonsterIdentifier::fromString('a')),
            new TournamentParticipant(MonsterIdentifier::fromString('b')),
            new TournamentParticipant(MonsterIdentifier::fromString('c')),
            new TournamentParticipant(MonsterIdentifier::fromString('d')),
            new TournamentParticipant(MonsterIdentifier::fromString('e')),
        ];

        // swiss standings as monster string => score
        $standings = [
            'a' => 9,
            'b' => 6,
            'c' => 6,
            'd' => 3,
            'e' => 0,
        ];

        $service = new TopBracketService();

        //! @section Act
        $bracket = $service->createTopSingleEliminationBracket($participants, $standings, 4);
        $round1 = $bracket->getCurrentRoundMatches();

        //! @section Assert
        $this->assertCount(2, $round1);
        $this->assertSame('a', $round1[0]->getParticipant1()->getMonster()->__toString());
        $this->assertSame('d', $round1[0]->getParticipant2()->getMonster()->__toString());
        $this->assertSame('b', $round1[1]->getParticipant1()->getMonster()->__toString());
        $this->assertSame('c', $round1[1]->getParticipant2()->getMonster()->__toString());
    }
}


