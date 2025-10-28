<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Service\SwissTournamentService;
use App\Service\TournamentManager;
use App\Repository\TournamentRepository;
use App\Type\MonsterIdentifier;

final class TournamentFlowIntegrationTest extends TestCase
{
    //! @brief Test complete tournament flow from creation to completion
    public function test_complete_tournament_flow(): void
    {
        //! @section Arrange
        $repository = new TournamentRepository();
        $tournamentService = new SwissTournamentService();
        $tournamentManager = new TournamentManager($repository, $tournamentService);
        
        $participants = [
            MonsterIdentifier::fromString('pikachu'),
            MonsterIdentifier::fromString('charizard'),
            MonsterIdentifier::fromString('blastoise'),
            MonsterIdentifier::fromString('venusaur'),
        ];
        
        $userEmail = 'test@example.com';
        
        //! @section Act
        $tournament = $tournamentManager->createTournament($participants, $userEmail);
        $this->assertNotNull($tournament);
        
        // Complete all rounds
        $totalRounds = $tournamentService->calculateTotalRounds(count($participants));
        for ($round = 0; $round < $totalRounds; $round++) {
            $pairings = $tournamentManager->getCurrentRoundPairings($tournament->getId());
            $this->assertNotEmpty($pairings);
            
            // Simulate match results
            foreach ($pairings as $pairing) {
                if (count($pairing) === 2) {
                    $winner = $pairing[0]; // Arbitrary winner selection
                    $tournamentManager->recordMatchResult($tournament->getId(), $pairing[0], $pairing[1], 'win', $winner);
                }
            }
            
            $tournamentManager->advanceToNextRound($tournament->getId());
        }
        
        //! @section Assert
        $finalTournament = $tournamentManager->getTournament($tournament->getId());
        $this->assertTrue($finalTournament->isComplete());
        
        $finalStandings = $tournamentManager->getFinalStandings($tournament->getId());
        $this->assertCount(4, $finalStandings);
        
        // Verify standings are sorted by score
        $scores = array_column($finalStandings, 'score');
        $sortedScores = $scores;
        rsort($sortedScores);
        $this->assertSame($sortedScores, $scores);
    }

    //! @brief Test tournament persistence across sessions
    public function test_tournament_persistence(): void
    {
        //! @section Arrange
        $repository = new TournamentRepository();
        $tournamentService = new SwissTournamentService();
        $tournamentManager = new TournamentManager($repository, $tournamentService);
        
        $participants = [
            MonsterIdentifier::fromString('pikachu'),
            MonsterIdentifier::fromString('charizard'),
        ];
        
        $userEmail = 'test@example.com';
        
        //! @section Act
        $tournament = $tournamentManager->createTournament($participants, $userEmail);
        $tournamentId = $tournament->getId();
        
        // Simulate session end and restart
        $newTournamentManager = new TournamentManager($repository, $tournamentService);
        $retrievedTournament = $newTournamentManager->getTournament($tournamentId);
        
        //! @section Assert
        $this->assertNotNull($retrievedTournament);
        $this->assertSame($tournamentId, $retrievedTournament->getId());
        $this->assertSame($userEmail, $retrievedTournament->getUserEmail());
        $this->assertSame(2, $retrievedTournament->getParticipantCount());
    }

    //! @brief Test tournament with multiple users
    public function test_multiple_user_tournaments(): void
    {
        //! @section Arrange
        $repository = new TournamentRepository();
        $tournamentService = new SwissTournamentService();
        $tournamentManager = new TournamentManager($repository, $tournamentService);
        
        $participants = [
            MonsterIdentifier::fromString('pikachu'),
            MonsterIdentifier::fromString('charizard'),
        ];
        
        $user1Email = 'user1@example.com';
        $user2Email = 'user2@example.com';
        
        //! @section Act
        $tournament1 = $tournamentManager->createTournament($participants, $user1Email);
        $tournament2 = $tournamentManager->createTournament($participants, $user2Email);
        
        //! @section Assert
        $this->assertNotSame($tournament1->getId(), $tournament2->getId());
        $this->assertSame($user1Email, $tournament1->getUserEmail());
        $this->assertSame($user2Email, $tournament2->getUserEmail());
        
        // Verify tournaments are isolated
        $user1Tournaments = $tournamentManager->getUserTournaments($user1Email);
        $user2Tournaments = $tournamentManager->getUserTournaments($user2Email);
        
        $this->assertCount(1, $user1Tournaments);
        $this->assertCount(1, $user2Tournaments);
        $this->assertSame($tournament1->getId(), $user1Tournaments[0]->getId());
        $this->assertSame($tournament2->getId(), $user2Tournaments[0]->getId());
    }

    //! @brief Test tournament with incomplete rounds
    public function test_tournament_with_incomplete_rounds(): void
    {
        //! @section Arrange
        $repository = new TournamentRepository();
        $tournamentService = new SwissTournamentService();
        $tournamentManager = new TournamentManager($repository, $tournamentService);
        
        $participants = [
            MonsterIdentifier::fromString('pikachu'),
            MonsterIdentifier::fromString('charizard'),
            MonsterIdentifier::fromString('blastoise'),
            MonsterIdentifier::fromString('venusaur'),
        ];
        
        $userEmail = 'test@example.com';
        
        //! @section Act
        $tournament = $tournamentManager->createTournament($participants, $userEmail);
        
        // Complete only first round
        $pairings = $tournamentManager->getCurrentRoundPairings($tournament->getId());
        foreach ($pairings as $pairing) {
            if (count($pairing) === 2) {
                $tournamentManager->recordMatchResult($tournament->getId(), $pairing[0], $pairing[1], 'win', $pairing[0]);
            }
        }
        
        $tournamentManager->advanceToNextRound($tournament->getId());
        
        //! @section Assert
        $tournament = $tournamentManager->getTournament($tournament->getId());
        $this->assertSame(1, $tournament->getCurrentRound());
        $this->assertFalse($tournament->isComplete());
        
        $standings = $tournamentManager->getCurrentStandings($tournament->getId());
        $this->assertCount(4, $standings);
        
        // Verify some participants have scores
        $hasScoredParticipants = false;
        foreach ($standings as $standing) {
            if ($standing['score'] > 0) {
                $hasScoredParticipants = true;
                break;
            }
        }
        $this->assertTrue($hasScoredParticipants);
    }

    //! @brief Test tournament with draw results
    public function test_tournament_with_draws(): void
    {
        //! @section Arrange
        $repository = new TournamentRepository();
        $tournamentService = new SwissTournamentService();
        $tournamentManager = new TournamentManager($repository, $tournamentService);
        
        $participants = [
            MonsterIdentifier::fromString('pikachu'),
            MonsterIdentifier::fromString('charizard'),
        ];
        
        $userEmail = 'test@example.com';
        
        //! @section Act
        $tournament = $tournamentManager->createTournament($participants, $userEmail);
        
        $pairings = $tournamentManager->getCurrentRoundPairings($tournament->getId());
        $pairing = $pairings[0];
        
        // Record a draw
        $tournamentManager->recordMatchResult($tournament->getId(), $pairing[0], $pairing[1], 'draw', null);
        
        //! @section Assert
        $standings = $tournamentManager->getCurrentStandings($tournament->getId());
        $this->assertCount(2, $standings);
        
        // Both participants should have 1 point
        foreach ($standings as $standing) {
            $this->assertSame(1, $standing['score']);
        }
    }

    //! @brief Test tournament error handling
    public function test_tournament_error_handling(): void
    {
        //! @section Arrange
        $repository = new TournamentRepository();
        $tournamentService = new SwissTournamentService();
        $tournamentManager = new TournamentManager($repository, $tournamentService);
        
        //! @section Act & Assert
        // Test invalid tournament ID
        $this->expectException(InvalidArgumentException::class);
        $tournamentManager->getTournament('invalid-id');
        
        // Test recording result for non-existent tournament
        $this->expectException(InvalidArgumentException::class);
        $tournamentManager->recordMatchResult('invalid-id', 
            MonsterIdentifier::fromString('pikachu'), 
            MonsterIdentifier::fromString('charizard'), 
            'win', 
            MonsterIdentifier::fromString('pikachu')
        );
    }

    //! @brief Test tournament with single participant
    public function test_tournament_single_participant(): void
    {
        //! @section Arrange
        $repository = new TournamentRepository();
        $tournamentService = new SwissTournamentService();
        $tournamentManager = new TournamentManager($repository, $tournamentService);
        
        $participants = [MonsterIdentifier::fromString('pikachu')];
        $userEmail = 'test@example.com';
        
        //! @section Act
        $tournament = $tournamentManager->createTournament($participants, $userEmail);
        
        //! @section Assert
        $this->assertNotNull($tournament);
        $this->assertTrue($tournament->isComplete()); // Single participant tournament is immediately complete
        
        $standings = $tournamentManager->getFinalStandings($tournament->getId());
        $this->assertCount(1, $standings);
        $this->assertSame('pikachu', $standings[0]['monster']->toString());
    }

    //! @brief Test tournament cleanup
    public function test_tournament_cleanup(): void
    {
        //! @section Arrange
        $repository = new TournamentRepository();
        $tournamentService = new SwissTournamentService();
        $tournamentManager = new TournamentManager($repository, $tournamentService);
        
        $participants = [
            MonsterIdentifier::fromString('pikachu'),
            MonsterIdentifier::fromString('charizard'),
        ];
        
        $userEmail = 'test@example.com';
        
        //! @section Act
        $tournament = $tournamentManager->createTournament($participants, $userEmail);
        $tournamentId = $tournament->getId();
        
        $tournamentManager->deleteTournament($tournamentId);
        
        //! @section Assert
        $this->expectException(InvalidArgumentException::class);
        $tournamentManager->getTournament($tournamentId);
    }
}
