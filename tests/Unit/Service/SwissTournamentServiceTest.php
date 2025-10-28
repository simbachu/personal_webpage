<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Service\SwissTournamentService;
use App\Type\MonsterIdentifier;

final class SwissTournamentServiceTest extends TestCase
{
    //! @brief Test Swiss pairing with even number of participants
    public function test_swiss_pairing_even_participants(): void
    {
        //! @section Arrange
        $participants = [
            MonsterIdentifier::fromString('pikachu'),
            MonsterIdentifier::fromString('charizard'),
            MonsterIdentifier::fromString('blastoise'),
            MonsterIdentifier::fromString('venusaur'),
        ];
        
        $service = new SwissTournamentService();
        
        //! @section Act
        $pairings = $service->generatePairings($participants, []);
        
        //! @section Assert
        $this->assertCount(2, $pairings);
        $this->assertCount(2, $pairings[0]);
        $this->assertCount(2, $pairings[1]);
        
        // Verify no participant appears twice
        $allPairedParticipants = array_merge($pairings[0], $pairings[1]);
        $uniqueParticipants = array_unique($allPairedParticipants);
        $this->assertCount(4, $uniqueParticipants);
    }

    //! @brief Test Swiss pairing with odd number of participants (bye handling)
    public function test_swiss_pairing_odd_participants(): void
    {
        //! @section Arrange
        $participants = [
            MonsterIdentifier::fromString('pikachu'),
            MonsterIdentifier::fromString('charizard'),
            MonsterIdentifier::fromString('blastoise'),
            MonsterIdentifier::fromString('venusaur'),
            MonsterIdentifier::fromString('mewtwo'),
        ];
        
        $service = new SwissTournamentService();
        
        //! @section Act
        $pairings = $service->generatePairings($participants, []);
        
        //! @section Assert
        $this->assertCount(3, $pairings); // 5 participants = 2 pairings of 2 + 1 bye
        
        // Two pairings should have 2 participants, one should have 1 (bye)
        $pairingCounts = [count($pairings[0]), count($pairings[1]), count($pairings[2])];
        $this->assertContains(2, $pairingCounts);
        $this->assertContains(2, $pairingCounts);
        $this->assertContains(1, $pairingCounts);
    }

    //! @brief Test Swiss pairing avoids repeat matchups
    public function test_swiss_pairing_avoids_repeats(): void
    {
        //! @section Arrange
        $participants = [
            MonsterIdentifier::fromString('pikachu'),
            MonsterIdentifier::fromString('charizard'),
            MonsterIdentifier::fromString('blastoise'),
            MonsterIdentifier::fromString('venusaur'),
        ];
        
        $previousMatchups = [
            [MonsterIdentifier::fromString('pikachu'), MonsterIdentifier::fromString('charizard')],
            [MonsterIdentifier::fromString('blastoise'), MonsterIdentifier::fromString('venusaur')],
        ];
        
        $service = new SwissTournamentService();
        
        //! @section Act
        $pairings = $service->generatePairings($participants, $previousMatchups);
        
        //! @section Assert
        // Verify no repeat matchups
        foreach ($pairings as $pairing) {
            if (count($pairing) === 2) {
                $this->assertNotContains(
                    [$pairing[0], $pairing[1]], 
                    $previousMatchups
                );
                $this->assertNotContains(
                    [$pairing[1], $pairing[0]], 
                    $previousMatchups
                );
            }
        }
    }

    //! @brief Test Swiss pairing with standings-based matching
    public function test_swiss_pairing_with_standings(): void
    {
        //! @section Arrange
        $participants = [
            MonsterIdentifier::fromString('pikachu'),
            MonsterIdentifier::fromString('charizard'),
            MonsterIdentifier::fromString('blastoise'),
            MonsterIdentifier::fromString('venusaur'),
        ];
        
        $standings = [
            'pikachu' => 6, // 2 wins
            'charizard' => 3, // 1 win
            'blastoise' => 3, // 1 win
            'venusaur' => 0, // 0 wins
        ];
        
        $service = new SwissTournamentService();
        
        //! @section Act
        $pairings = $service->generatePairings($participants, [], $standings);
        
        //! @section Assert
        // Swiss pairing should pair participants with similar scores
        // pikachu (6) should pair with charizard/blastoise (3), not venusaur (0)
        $this->assertCount(2, $pairings);
        
        // Verify that pikachu is paired with someone who has 3 points
        $pikachuPaired = false;
        foreach ($pairings as $pairing) {
            if (count($pairing) === 2) {
                $participants = [$pairing[0]->__toString(), $pairing[1]->__toString()];
                if (in_array('pikachu', $participants)) {
                    $pikachuPaired = true;
                    // Pikachu should be paired with someone who has 3 points
                    $opponent = $pairing[0]->__toString() === 'pikachu' ? $pairing[1]->__toString() : $pairing[0]->__toString();
                    $this->assertContains($opponent, ['charizard', 'blastoise'], 'Pikachu should be paired with charizard or blastoise (both have 3 points)');
                }
            }
        }
        $this->assertTrue($pikachuPaired, 'Pikachu should be paired with someone');
    }

    //! @brief Test tournament scoring system
    public function test_tournament_scoring(): void
    {
        //! @section Arrange
        $service = new SwissTournamentService();
        
        //! @section Act & Assert
        $winScore = $service->getScoreForResult('win');
        $lossScore = $service->getScoreForResult('loss');
        $drawScore = $service->getScoreForResult('draw');
        
        $this->assertSame(3, $winScore);
        $this->assertSame(0, $lossScore);
        $this->assertSame(1, $drawScore);
    }

    //! @brief Test round progression logic
    public function test_round_progression(): void
    {
        //! @section Arrange
        $service = new SwissTournamentService();
        $participantCount = 8;
        
        //! @section Act
        $totalRounds = $service->calculateTotalRounds($participantCount);
        
        //! @section Assert
        // Swiss tournaments typically use 4-6 rounds for 8 participants
        $this->assertGreaterThanOrEqual(4, $totalRounds);
        $this->assertLessThanOrEqual(6, $totalRounds);
    }

    //! @brief Test tournament state management
    public function test_tournament_state_management(): void
    {
        //! @section Arrange
        $service = new SwissTournamentService();
        $participants = [
            MonsterIdentifier::fromString('pikachu'),
            MonsterIdentifier::fromString('charizard'),
        ];
        
        //! @section Act
        $tournament = $service->createTournament($participants, 'test@example.com');
        
        //! @section Assert
        $this->assertSame('test@example.com', $tournament->getUserEmail());
        $this->assertSame(2, $tournament->getParticipantCount());
        $this->assertSame(0, $tournament->getCurrentRound());
        $this->assertFalse($tournament->isComplete());
    }

    //! @brief Test tournament completion detection
    public function test_tournament_completion(): void
    {
        //! @section Arrange
        $service = new SwissTournamentService();
        $participants = [
            MonsterIdentifier::fromString('pikachu'),
            MonsterIdentifier::fromString('charizard'),
        ];
        
        $tournament = $service->createTournament($participants, 'test@example.com');
        $totalRounds = $service->calculateTotalRounds(2);
        
        //! @section Act
        for ($round = 0; $round < $totalRounds; $round++) {
            $tournament->advanceRound();
        }
        
        //! @section Assert
        $this->assertTrue($tournament->isComplete());
        $this->assertSame($totalRounds, $tournament->getCurrentRound());
    }

    //! @brief Test edge case: single participant tournament
    public function test_single_participant_tournament(): void
    {
        //! @section Arrange
        $service = new SwissTournamentService();
        $participants = [MonsterIdentifier::fromString('pikachu')];
        
        //! @section Act
        $pairings = $service->generatePairings($participants, []);
        
        //! @section Assert
        $this->assertCount(1, $pairings);
        $this->assertCount(1, $pairings[0]);
        $this->assertTrue($pairings[0][0]->equals(MonsterIdentifier::fromString('pikachu')));
    }

    //! @brief Test edge case: empty participants list
    public function test_empty_participants_list(): void
    {
        //! @section Arrange
        $service = new SwissTournamentService();
        
        //! @section Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $service->generatePairings([], []);
    }

    //! @brief Test standings calculation after multiple rounds
    public function test_standings_calculation(): void
    {
        //! @section Arrange
        $service = new SwissTournamentService();
        $participants = [
            MonsterIdentifier::fromString('pikachu'),
            MonsterIdentifier::fromString('charizard'),
            MonsterIdentifier::fromString('blastoise'),
            MonsterIdentifier::fromString('venusaur'),
        ];
        
        $matchResults = [
            [MonsterIdentifier::fromString('pikachu'), MonsterIdentifier::fromString('charizard'), 'win'],
            [MonsterIdentifier::fromString('blastoise'), MonsterIdentifier::fromString('venusaur'), 'win'],
            [MonsterIdentifier::fromString('pikachu'), MonsterIdentifier::fromString('blastoise'), 'win'],
            [MonsterIdentifier::fromString('charizard'), MonsterIdentifier::fromString('venusaur'), 'draw'],
        ];
        
        //! @section Act
        $standings = $service->calculateStandings($participants, $matchResults);
        
        //! @section Assert
        $this->assertCount(4, $standings);
        $this->assertSame(6, $standings['pikachu']); // 2 wins
        $this->assertSame(1, $standings['charizard']); // 1 draw
        $this->assertSame(3, $standings['blastoise']); // 1 win
        $this->assertSame(1, $standings['venusaur']); // 1 draw
    }

    //! @brief Test tie-breaking mechanism
    public function test_tie_breaking(): void
    {
        //! @section Arrange
        $service = new SwissTournamentService();
        $participants = [
            MonsterIdentifier::fromString('pikachu'),
            MonsterIdentifier::fromString('charizard'),
            MonsterIdentifier::fromString('blastoise'),
        ];
        
        $standings = [
            'pikachu' => 3,
            'charizard' => 3,
            'blastoise' => 3,
        ];
        
        //! @section Act
        $sortedStandings = $service->sortStandingsByTieBreaker($standings, $participants);
        
        //! @section Assert
        $this->assertCount(3, $sortedStandings);
        // All should have same score, but order should be deterministic
        $this->assertSame(3, $sortedStandings[0]['score']);
        $this->assertSame(3, $sortedStandings[1]['score']);
        $this->assertSame(3, $sortedStandings[2]['score']);
    }
}
