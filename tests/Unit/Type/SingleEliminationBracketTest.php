<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Type\MonsterIdentifier;
use App\Type\TournamentParticipant;
use App\Type\SingleEliminationBracket;
use App\Type\TournamentResult;

final class SingleEliminationBracketTest extends TestCase
{
    //! @brief Test seeding for top 8 single-elimination (1v8, 2v7, 3v6, 4v5)
    public function test_seeding_top8(): void
    {
        //! @section Arrange
        $participants = [
            new TournamentParticipant(MonsterIdentifier::fromString('seed1')),
            new TournamentParticipant(MonsterIdentifier::fromString('seed2')),
            new TournamentParticipant(MonsterIdentifier::fromString('seed3')),
            new TournamentParticipant(MonsterIdentifier::fromString('seed4')),
            new TournamentParticipant(MonsterIdentifier::fromString('seed5')),
            new TournamentParticipant(MonsterIdentifier::fromString('seed6')),
            new TournamentParticipant(MonsterIdentifier::fromString('seed7')),
            new TournamentParticipant(MonsterIdentifier::fromString('seed8')),
        ];

        //! @section Act
        $bracket = new SingleEliminationBracket($participants);
        $round1 = $bracket->getCurrentRoundMatches();

        //! @section Assert
        $this->assertCount(4, $round1);
        $this->assertSame('seed1', $round1[0]->getParticipant1()->getMonster()->__toString());
        $this->assertSame('seed8', $round1[0]->getParticipant2()->getMonster()->__toString());
        $this->assertSame('seed4', $round1[1]->getParticipant1()->getMonster()->__toString());
        $this->assertSame('seed5', $round1[1]->getParticipant2()->getMonster()->__toString());
        $this->assertSame('seed3', $round1[2]->getParticipant1()->getMonster()->__toString());
        $this->assertSame('seed6', $round1[2]->getParticipant2()->getMonster()->__toString());
        $this->assertSame('seed2', $round1[3]->getParticipant1()->getMonster()->__toString());
        $this->assertSame('seed7', $round1[3]->getParticipant2()->getMonster()->__toString());
    }

    //! @brief Test progression from quarterfinals to semifinals to finals and winner detection
    public function test_progression_and_winner(): void
    {
        //! @section Arrange
        $participants = [];
        for ($i = 1; $i <= 8; $i++) {
            $participants[] = new TournamentParticipant(MonsterIdentifier::fromString('seed' . $i));
        }
        $bracket = new SingleEliminationBracket($participants);

        //! @section Act
        // Round 1: higher seed always wins
        foreach ($bracket->getCurrentRoundMatches() as $match) {
            $winner = $match->getParticipant1();
            $match->recordResult(new TournamentResult('win', $winner));
        }
        $bracket->advanceRound();

        // Round 2
        foreach ($bracket->getCurrentRoundMatches() as $match) {
            $winner = $match->getParticipant1();
            $match->recordResult(new TournamentResult('win', $winner));
        }
        $bracket->advanceRound();

        // Final
        $finals = $bracket->getCurrentRoundMatches();
        $this->assertCount(1, $finals);
        $finals[0]->recordResult(new TournamentResult('win', $finals[0]->getParticipant1()));
        $bracket->advanceRound();

        //! @section Assert
        $this->assertTrue($bracket->isComplete());
        $this->assertNotNull($bracket->getWinner());
        $this->assertSame('seed1', $bracket->getWinner()->getMonster()->__toString());
    }
}


