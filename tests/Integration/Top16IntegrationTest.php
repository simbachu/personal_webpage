<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Service\SwissTournamentService;
use App\Service\TopBracketService;
use App\Type\MonsterIdentifier;
use App\Type\TournamentParticipant;

final class Top16IntegrationTest extends TestCase
{
    //! @brief Swiss standings → Top 16 bracket seeding and progression
    public function test_swiss_to_top16_bracket(): void
    {
        //! @section Arrange
        $swiss = new SwissTournamentService();
        $topService = new TopBracketService();

        // 16 participants named p1..p16. Pretend Swiss produced descending scores
        $participants = [];
        for ($i = 1; $i <= 16; $i++) {
            $participants[] = new TournamentParticipant(MonsterIdentifier::fromString('p' . $i));
        }

        $standings = [];
        for ($i = 1; $i <= 16; $i++) {
            // p1 highest, p16 lowest
            $standings['p' . $i] = 17 - $i;
        }

        //! @section Act
        $bracket = $topService->createTopSingleEliminationBracket($participants, $standings, 16);
        $round1 = $bracket->getCurrentRoundMatches();

        //! @section Assert
        // Seeding should place 1v16, 8v9, 5v12, 4v13, 3v14, 6v11, 7v10, 2v15
        $this->assertCount(8, $round1);
        $this->assertSame('p1',  $round1[0]->getParticipant1()->getMonster()->__toString());
        $this->assertSame('p16', $round1[0]->getParticipant2()->getMonster()->__toString());

        // Progress the bracket by picking participant1 as winner each match
        foreach ($round1 as $match) {
            $match->recordResult(new \App\Type\TournamentResult('win', $match->getParticipant1()));
        }
        $bracket->advanceRound();

        $round2 = $bracket->getCurrentRoundMatches();
        $this->assertCount(4, $round2);
        foreach ($round2 as $match) {
            $match->recordResult(new \App\Type\TournamentResult('win', $match->getParticipant1()));
        }
        $bracket->advanceRound();

        $round3 = $bracket->getCurrentRoundMatches();
        $this->assertCount(2, $round3);
        foreach ($round3 as $match) {
            $match->recordResult(new \App\Type\TournamentResult('win', $match->getParticipant1()));
        }
        $bracket->advanceRound();

        $finals = $bracket->getCurrentRoundMatches();
        $this->assertCount(1, $finals);
        $finals[0]->recordResult(new \App\Type\TournamentResult('win', $finals[0]->getParticipant1()));
        $bracket->advanceRound();

        $this->assertNotNull($bracket->getWinner());
        $this->assertSame('p1', $bracket->getWinner()->getMonster()->__toString());
    }
}


