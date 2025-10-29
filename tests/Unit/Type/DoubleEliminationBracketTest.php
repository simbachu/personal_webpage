<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Type\MonsterIdentifier;
use App\Type\TournamentParticipant;
use App\Type\DoubleEliminationBracket;
use App\Type\TournamentResult;

final class DoubleEliminationBracketTest extends TestCase
{
    //! @brief Test winners bracket seeding for 8 participants
    public function test_winners_bracket_seeding_top8(): void
    {
        //! @section Arrange
        $participants = [];
        for ($i = 1; $i <= 8; $i++) {
            $participants[] = new TournamentParticipant(MonsterIdentifier::fromString('seed' . $i));
        }

        //! @section Act
        $bracket = new DoubleEliminationBracket($participants);
        $matches = $bracket->getCurrentRoundMatches();

        //! @section Assert
        $this->assertCount(4, $matches);
        $this->assertSame('seed1', $matches[0]->getParticipant1()->getMonster()->__toString());
        $this->assertSame('seed8', $matches[0]->getParticipant2()->getMonster()->__toString());
        $this->assertSame('seed4', $matches[1]->getParticipant1()->getMonster()->__toString());
        $this->assertSame('seed5', $matches[1]->getParticipant2()->getMonster()->__toString());
        $this->assertSame('seed3', $matches[2]->getParticipant1()->getMonster()->__toString());
        $this->assertSame('seed6', $matches[2]->getParticipant2()->getMonster()->__toString());
        $this->assertSame('seed2', $matches[3]->getParticipant1()->getMonster()->__toString());
        $this->assertSame('seed7', $matches[3]->getParticipant2()->getMonster()->__toString());
    }

    //! @brief Test full progression without bracket reset (WB champion wins final)
    public function test_progression_without_reset(): void
    {
        //! @section Arrange
        $participants = [];
        for ($i = 1; $i <= 8; $i++) {
            $participants[] = new TournamentParticipant(MonsterIdentifier::fromString('seed' . $i));
        }
        $bracket = new DoubleEliminationBracket($participants);

        //! @section Act
        // WB R1: participant1 always wins
        foreach ($bracket->getCurrentRoundMatches() as $m) {
            $m->recordResult(new TournamentResult('win', $m->getParticipant1()));
        }
        $bracket->advanceRound();

        // LB R1: participant1 wins
        foreach ($bracket->getCurrentRoundMatches() as $m) {
            $m->recordResult(new TournamentResult('win', $m->getParticipant1()));
        }
        $bracket->advanceRound();

        // WB R2
        foreach ($bracket->getCurrentRoundMatches() as $m) {
            $m->recordResult(new TournamentResult('win', $m->getParticipant1()));
        }
        $bracket->advanceRound();

        // LB R2
        foreach ($bracket->getCurrentRoundMatches() as $m) {
            $m->recordResult(new TournamentResult('win', $m->getParticipant1()));
        }
        $bracket->advanceRound();

        // LB R3
        foreach ($bracket->getCurrentRoundMatches() as $m) {
            $m->recordResult(new TournamentResult('win', $m->getParticipant1()));
        }
        $bracket->advanceRound();

        // WB Final
        foreach ($bracket->getCurrentRoundMatches() as $m) {
            $m->recordResult(new TournamentResult('win', $m->getParticipant1()));
        }
        $bracket->advanceRound();

        // LB Final
        foreach ($bracket->getCurrentRoundMatches() as $m) {
            $m->recordResult(new TournamentResult('win', $m->getParticipant1()));
        }
        $bracket->advanceRound();

        // Grand Final (WB champ vs LB champ) - WB champ wins, no reset
        $finals = $bracket->getCurrentRoundMatches();
        $this->assertCount(1, $finals);
        $finals[0]->recordResult(new TournamentResult('win', $finals[0]->getParticipant1()));
        $bracket->advanceRound();

        //! @section Assert
        $this->assertTrue($bracket->isComplete());
        $this->assertSame('seed1', $bracket->getWinner()->getMonster()->__toString());
    }

    //! @brief Test grand finals bracket reset when LB champion wins first final
    public function test_grand_final_with_reset(): void
    {
        //! @section Arrange
        $participants = [];
        for ($i = 1; $i <= 8; $i++) {
            $participants[] = new TournamentParticipant(MonsterIdentifier::fromString('seed' . $i));
        }
        $bracket = new DoubleEliminationBracket($participants);

        // Drive deterministically to grand final (8-player flow):
        // WB-R1 -> LB-R1 -> WB-R2 -> LB-R2 -> LB-R3 -> WB-Final -> LB-Final -> GF-1
        foreach ([4,2,2,2,1,1,1] as $expectedCount) {
            $matches = $bracket->getCurrentRoundMatches();
            $this->assertCount($expectedCount, $matches);
            foreach ($matches as $m) {
                $m->recordResult(new TournamentResult('win', $m->getParticipant1()));
            }
            $bracket->advanceRound();
        }
        // Now at GF-1

        //! @section Act
        // First grand final: let LB champ (participant2) win to trigger reset
        $gf = $bracket->getCurrentRoundMatches();
        $this->assertCount(1, $gf);
        $gf[0]->recordResult(new TournamentResult('win', $gf[0]->getParticipant2()));
        $bracket->advanceRound();

        // Reset final should now exist
        $reset = $bracket->getCurrentRoundMatches();
        $this->assertCount(1, $reset);
        // WB side wins the reset
        $reset[0]->recordResult(new TournamentResult('win', $reset[0]->getParticipant1()));
        $bracket->advanceRound();

        //! @section Assert
        $this->assertTrue($bracket->isComplete());
        $this->assertNotNull($bracket->getWinner());
    }

    //! @brief When reset is disabled, LB champion winning GF-1 decides the tournament
    public function test_grand_final_without_reset_option(): void
    {
        //! @section Arrange
        $participants = [];
        for ($i = 1; $i <= 8; $i++) {
            $participants[] = new TournamentParticipant(MonsterIdentifier::fromString('seed' . $i));
        }
        // disable reset
        $bracket = new DoubleEliminationBracket($participants, enableReset: false);

        // Drive deterministically to GF-1
        foreach ([4,2,2,2,1,1,1] as $expectedCount) {
            $matches = $bracket->getCurrentRoundMatches();
            $this->assertCount($expectedCount, $matches);
            foreach ($matches as $m) {
                $m->recordResult(new TournamentResult('win', $m->getParticipant1()));
            }
            $bracket->advanceRound();
        }

        //! @section Act
        // In GF-1, let LB champion (participant2) win — should complete without reset
        $gf = $bracket->getCurrentRoundMatches();
        $this->assertCount(1, $gf);
        $gf[0]->recordResult(new TournamentResult('win', $gf[0]->getParticipant2()));
        $bracket->advanceRound();

        //! @section Assert
        $this->assertTrue($bracket->isComplete());
        $this->assertNotNull($bracket->getWinner());
    }
}


