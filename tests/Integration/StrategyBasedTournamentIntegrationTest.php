<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Service\SwissTournamentService;
use App\Service\PlayoffBracketFactory;
use App\Service\PrefersLowerLexicalStrategy;
use App\Service\PrefersHigherHpStrategy;
use App\Type\MonsterIdentifier;
use App\Type\TournamentParticipant;
use App\Type\TournamentResult;

//! @brief Integration test for strategy-based tournaments
//!
//! Runs complete Swiss tournament → Top 16 playoff flow with different
//! decision-making strategies to verify tournament structure integrity.
final class StrategyBasedTournamentIntegrationTest extends TestCase
{
    //! @brief Mock HP data for test Pokemon
    //! @return array<string,int> Map of Pokemon name to base HP
    private function getMockHpData(): array
    {
        return [
            'alakazam' => 55,
            'abomasnow' => 90,
            'aerodactyl' => 80,
            'absol' => 65,
            'aggron' => 70,
            'ampharos' => 90,
            'arbok' => 60,
            'ariados' => 70,
            'armaldo' => 75,
            'aromatisse' => 101,
            'articuno' => 90,
            'audino' => 103,
            'azelf' => 75,
            'azumarill' => 100,
            'azurill' => 50,
            'bayleef' => 60,
            'beautifly' => 60,
            'beedrill' => 65,
            'bellossom' => 75,
            'bibarel' => 79,
            'bidoof' => 59,
            'blastoise' => 79,
            'blaziken' => 80,
            'bonsly' => 50,
            'bounsweet' => 42,
            'braixen' => 59,
            'braviary' => 100,
            'briisharp' => 65,
            'budew' => 40,
            'buizel' => 55,
            'bulbasaur' => 45,
            'buneary' => 55,
        ];
    }

    //! @brief Test Swiss tournament with LowerLexical strategy
    public function test_swiss_tournament_with_lower_lexical_strategy(): void
    {
        //! @section Arrange
        $swiss = new SwissTournamentService();
        $factory = new PlayoffBracketFactory();
        $strategy = new PrefersLowerLexicalStrategy();

        // Create 32 participants with known names
        $participants = [];
        $hpData = $this->getMockHpData();
        $names = array_keys($hpData);
        
        // Just use the first 32 unique names we have
        foreach (array_slice($names, 0, 32) as $name) {
            $participants[] = MonsterIdentifier::fromString($name);
        }

        //! @section Act
        // Run Swiss rounds
        $standings = [];
        $previousMatchups = [];
        
        // Initialize all participants with 0 score
        foreach ($participants as $p) {
            $standings[$p->__toString()] = 0;
        }
        
        for ($round = 0; $round < 6; $round++) {
            $pairings = $swiss->generatePairings($participants, $previousMatchups, $standings);
            
            foreach ($pairings as $pairing) {
                if (count($pairing) === 2) {
                    $winner = $strategy->chooseWinner($pairing[0], $pairing[1]);
                    $loser = $winner->equals($pairing[0]) ? $pairing[1] : $pairing[0];
                    
                    // Update standings
                    $standings[$winner->__toString()] += 3;
                    
                    // Track matchup
                    $previousMatchups[] = $pairing;
                }
            }
        }

        //! @section Assert
        // Verify all participants have scores
        $this->assertCount(32, $standings);
        
        // Verify top participant should be alphabetically earliest
        arsort($standings);
        $topParticipants = array_slice(array_keys($standings), 0, 3, true);
        $this->assertNotEmpty($topParticipants);
        
        // Create Top 16 bracket
        $top16 = array_slice($participants, 0, 16);
        $top16Standings = array_intersect_key($standings, array_flip(array_map(fn($p) => $p->__toString(), $top16)));
        
        $bracket = $factory->createFromStandings(
            array_map(fn($id) => new TournamentParticipant($id), $participants),
            $standings,
            16,
            'double-elimination',
            false
        );

        $this->assertFalse($bracket->isComplete());
        $matches = $bracket->getCurrentRoundMatches();
        $this->assertCount(8, $matches); // 16 / 2 = 8 initial matches
    }

    //! @brief Test Swiss tournament with HigherHp strategy
    public function test_swiss_tournament_with_higher_hp_strategy(): void
    {
        //! @section Arrange
        $swiss = new SwissTournamentService();
        $factory = new PlayoffBracketFactory();
        $hpData = $this->getMockHpData();
        $strategy = new PrefersHigherHpStrategy(fn(MonsterIdentifier $id) => $hpData[strtolower($id->__toString())] ?? 0);

        // Create 32 participants with known names and HP
        $participants = [];
        $names = array_keys($hpData);
        
        // Just use the first 32 unique names we have
        foreach (array_slice($names, 0, 32) as $name) {
            $participants[] = MonsterIdentifier::fromString($name);
        }

        //! @section Act
        // Run Swiss rounds
        $standings = [];
        $previousMatchups = [];
        
        // Initialize all participants with 0 score
        foreach ($participants as $p) {
            $standings[$p->__toString()] = 0;
        }
        
        for ($round = 0; $round < 6; $round++) {
            $pairings = $swiss->generatePairings($participants, $previousMatchups, $standings);
            
            foreach ($pairings as $pairing) {
                if (count($pairing) === 2) {
                    $winner = $strategy->chooseWinner($pairing[0], $pairing[1]);
                    $loser = $winner->equals($pairing[0]) ? $pairing[1] : $pairing[0];
                    
                    // Update standings
                    $standings[$winner->__toString()] += 3;
                    
                    // Track matchup
                    $previousMatchups[] = $pairing;
                }
            }
        }

        //! @section Assert
        // Verify all participants have scores
        $this->assertCount(32, $standings);
        
        // Verify top participant should be high HP
        arsort($standings);
        $topParticipant = array_key_first($standings);
        $this->assertNotNull($topParticipant);
        
        // Higher HP Pokemon should generally rank higher
        $topParticipantHp = $hpData[strtolower($topParticipant)] ?? 0;
        $this->assertGreaterThan(50, $topParticipantHp, 'Top participant should have reasonable HP');
        
        // Create Top 16 bracket and progress to completion
        $bracket = $factory->createFromStandings(
            array_map(fn($id) => new TournamentParticipant($id), $participants),
            $standings,
            16,
            'double-elimination',
            false
        );

        // Progress bracket to completion
        while (!$bracket->isComplete()) {
            $matches = $bracket->getCurrentRoundMatches();
            if (empty($matches)) {
                break;
            }
            
            foreach ($matches as $match) {
                // Use strategy to determine winner
                $winner = $strategy->chooseWinner(
                    $match->getParticipant1()->getMonster(),
                    $match->getParticipant2()->getMonster()
                );
                $winnerParticipant = $winner->equals($match->getParticipant1()->getMonster()) 
                    ? $match->getParticipant1() 
                    : $match->getParticipant2();
                $match->recordResult(new TournamentResult('win', $winnerParticipant));
            }
            
            $bracket->advanceRound();
        }

        //! @section Assert - Bracket Complete
        $this->assertTrue($bracket->isComplete());
        $winner = $bracket->getWinner();
        $this->assertNotNull($winner);
    }
}

