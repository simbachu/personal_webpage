<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Type\TournamentParticipant;
use App\Type\TournamentMatch;
use App\Type\TournamentResult;
use App\Type\MonsterIdentifier;

final class TournamentTypesTest extends TestCase
{
    //! @brief Test TournamentParticipant creation and properties
    public function test_tournament_participant_creation(): void
    {
        //! @section Arrange
        $monster = MonsterIdentifier::fromString('pikachu');
        
        //! @section Act
        $participant = new TournamentParticipant($monster, 0);
        
        //! @section Assert
        $this->assertTrue($participant->getMonster()->equals($monster));
        $this->assertSame(0, $participant->getScore());
        $this->assertSame(0, $participant->getWins());
        $this->assertSame(0, $participant->getLosses());
        $this->assertSame(0, $participant->getDraws());
    }

    //! @brief Test TournamentParticipant score updates
    public function test_tournament_participant_score_updates(): void
    {
        //! @section Arrange
        $monster = MonsterIdentifier::fromString('pikachu');
        $participant = new TournamentParticipant($monster, 0);
        
        //! @section Act
        $participant->addWin();
        $participant->addWin();
        $participant->addDraw();
        
        //! @section Assert
        $this->assertSame(7, $participant->getScore()); // 2 wins (6) + 1 draw (1)
        $this->assertSame(2, $participant->getWins());
        $this->assertSame(0, $participant->getLosses());
        $this->assertSame(1, $participant->getDraws());
    }

    //! @brief Test TournamentMatch creation and properties
    public function test_tournament_match_creation(): void
    {
        //! @section Arrange
        $participant1 = new TournamentParticipant(MonsterIdentifier::fromString('pikachu'), 0);
        $participant2 = new TournamentParticipant(MonsterIdentifier::fromString('charizard'), 0);
        
        //! @section Act
        $match = new TournamentMatch($participant1, $participant2, 1);
        
        //! @section Assert
        $this->assertSame($participant1, $match->getParticipant1());
        $this->assertSame($participant2, $match->getParticipant2());
        $this->assertSame(1, $match->getRound());
        $this->assertNull($match->getResult());
        $this->assertFalse($match->isComplete());
    }

    //! @brief Test TournamentMatch result recording
    public function test_tournament_match_result_recording(): void
    {
        //! @section Arrange
        $participant1 = new TournamentParticipant(MonsterIdentifier::fromString('pikachu'), 0);
        $participant2 = new TournamentParticipant(MonsterIdentifier::fromString('charizard'), 0);
        $match = new TournamentMatch($participant1, $participant2, 1);
        
        //! @section Act
        $result = new TournamentResult('win', $participant1);
        $match->recordResult($result);
        
        //! @section Assert
        $this->assertTrue($match->isComplete());
        $this->assertSame($result, $match->getResult());
        $this->assertSame(3, $participant1->getScore());
        $this->assertSame(0, $participant2->getScore());
        $this->assertSame(1, $participant1->getWins());
        $this->assertSame(1, $participant2->getLosses());
    }

    //! @brief Test TournamentResult creation and validation
    public function test_tournament_result_creation(): void
    {
        //! @section Arrange
        $participant = new TournamentParticipant(MonsterIdentifier::fromString('pikachu'), 0);
        
        //! @section Act
        $winResult = new TournamentResult('win', $participant);
        $lossResult = new TournamentResult('loss', $participant);
        $drawResult = new TournamentResult('draw', null);
        
        //! @section Assert
        $this->assertSame('win', $winResult->getOutcome());
        $this->assertSame($participant, $winResult->getWinner());
        $this->assertSame(3, $winResult->getWinnerScore());
        $this->assertSame(0, $winResult->getLoserScore());
        
        $this->assertSame('loss', $lossResult->getOutcome());
        $this->assertSame(0, $lossResult->getWinnerScore());
        $this->assertSame(0, $lossResult->getLoserScore());
        
        $this->assertSame('draw', $drawResult->getOutcome());
        $this->assertSame(1, $drawResult->getWinnerScore());
        $this->assertSame(1, $drawResult->getLoserScore());
    }

    //! @brief Test TournamentResult with invalid outcome
    public function test_tournament_result_invalid_outcome(): void
    {
        //! @section Arrange
        $participant = new TournamentParticipant(MonsterIdentifier::fromString('pikachu'), 0);
        
        //! @section Act & Assert
        $this->expectException(InvalidArgumentException::class);
        new TournamentResult('invalid', $participant);
    }

    //! @brief Test TournamentParticipant equality
    public function test_tournament_participant_equality(): void
    {
        //! @section Arrange
        $monster1 = MonsterIdentifier::fromString('pikachu');
        $monster2 = MonsterIdentifier::fromString('pikachu');
        $monster3 = MonsterIdentifier::fromString('charizard');
        
        $participant1 = new TournamentParticipant($monster1, 0);
        $participant2 = new TournamentParticipant($monster2, 0);
        $participant3 = new TournamentParticipant($monster3, 0);
        
        //! @section Act & Assert
        $this->assertTrue($participant1->equals($participant2));
        $this->assertFalse($participant1->equals($participant3));
    }

    //! @brief Test TournamentMatch equality
    public function test_tournament_match_equality(): void
    {
        //! @section Arrange
        $participant1 = new TournamentParticipant(MonsterIdentifier::fromString('pikachu'), 0);
        $participant2 = new TournamentParticipant(MonsterIdentifier::fromString('charizard'), 0);
        
        $match1 = new TournamentMatch($participant1, $participant2, 1);
        $match2 = new TournamentMatch($participant1, $participant2, 1);
        $match3 = new TournamentMatch($participant2, $participant1, 1);
        
        //! @section Act & Assert
        $this->assertTrue($match1->equals($match2));
        $this->assertTrue($match1->equals($match3)); // Should be equal regardless of order
    }

    //! @brief Test TournamentParticipant string representation
    public function test_tournament_participant_to_string(): void
    {
        //! @section Arrange
        $monster = MonsterIdentifier::fromString('pikachu');
        $participant = new TournamentParticipant($monster, 0);
        $participant->addWin();
        $participant->addDraw();
        
        //! @section Act
        $string = $participant->toString();
        
        //! @section Assert
        $this->assertStringContainsString('pikachu', $string);
        $this->assertStringContainsString('4', $string); // 1 win (3) + 1 draw (1)
        $this->assertStringContainsString('1', $string); // wins
        $this->assertStringContainsString('1', $string); // draws
    }

    //! @brief Test TournamentMatch string representation
    public function test_tournament_match_to_string(): void
    {
        //! @section Arrange
        $participant1 = new TournamentParticipant(MonsterIdentifier::fromString('pikachu'), 0);
        $participant2 = new TournamentParticipant(MonsterIdentifier::fromString('charizard'), 0);
        $match = new TournamentMatch($participant1, $participant2, 1);
        
        //! @section Act
        $string = $match->toString();
        
        //! @section Assert
        $this->assertStringContainsString('pikachu', $string);
        $this->assertStringContainsString('charizard', $string);
        $this->assertStringContainsString('Round 1', $string);
    }

    //! @brief Test TournamentParticipant reset functionality
    public function test_tournament_participant_reset(): void
    {
        //! @section Arrange
        $monster = MonsterIdentifier::fromString('pikachu');
        $participant = new TournamentParticipant($monster, 0);
        $participant->addWin();
        $participant->addDraw();
        
        //! @section Act
        $participant->reset();
        
        //! @section Assert
        $this->assertSame(0, $participant->getScore());
        $this->assertSame(0, $participant->getWins());
        $this->assertSame(0, $participant->getLosses());
        $this->assertSame(0, $participant->getDraws());
    }

    //! @brief Test TournamentMatch incomplete state
    public function test_tournament_match_incomplete_state(): void
    {
        //! @section Arrange
        $participant1 = new TournamentParticipant(MonsterIdentifier::fromString('pikachu'), 0);
        $participant2 = new TournamentParticipant(MonsterIdentifier::fromString('charizard'), 0);
        $match = new TournamentMatch($participant1, $participant2, 1);
        
        //! @section Act & Assert
        $this->assertFalse($match->isComplete());
        $this->assertNull($match->getResult());
        
        // Attempting to get winner before completion should throw
        $this->expectException(RuntimeException::class);
        $match->getWinner();
    }
}
