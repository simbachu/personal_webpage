<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Service\TournamentFormatLoader;
use App\Service\SwissTournamentService;
use App\Service\PlayoffBracketFactory;
use App\Type\MonsterIdentifier;
use App\Type\TournamentParticipant;
use App\Type\TournamentResult;

final class ConfigDrivenTournamentIntegrationTest extends TestCase
{
    //! @brief Config-driven Swiss → Playoff flow to final winner
    public function test_config_driven_tournament_flow(): void
    {
        //! @section Arrange
        $loader = new TournamentFormatLoader();
        $swiss = new SwissTournamentService();
        $factory = new PlayoffBracketFactory();

        $configPath = __DIR__ . '/../../example_config.yaml';
        $format = $loader->load($configPath, 'favorite-pokemon');

        // Create 32 participants and generate mock Swiss standings descending p1..p32
        $participants = [];
        $standings = [];
        for ($i = 1; $i <= 32; $i++) {
            $name = 'p' . $i;
            $participants[] = MonsterIdentifier::fromString($name);
            $standings[$name] = 33 - $i;
        }

        // Create a Swiss tournament entity (smoke) and assert rounds computed
        $tournament = $swiss->createTournament($participants, 'test@example.com');
        $this->assertSame(32, $tournament->getParticipantCount());
        $this->assertGreaterThan(0, $tournament->getTotalRounds());

        //! @section Act
        // Build playoff bracket per config
        $playoff = $factory->createFromStandings(
            array_map(fn($id) => new TournamentParticipant($id), $participants),
            $standings,
            $format->playoffCutoff ?? 16,
            $format->playoff ?? 'single-elimination',
            $format->playoffReset
        );

        // Progress playoff to completion choosing participant1 as winner each match
        while (method_exists($playoff, 'getCurrentRoundMatches') && method_exists($playoff, 'isComplete') && !$playoff->isComplete()) {
            $matches = $playoff->getCurrentRoundMatches();
            if (empty($matches)) {
                break;
            }
            foreach ($matches as $m) {
                $m->recordResult(new TournamentResult('win', $m->getParticipant1()));
            }
            $playoff->advanceRound();
        }

        //! @section Assert
        $this->assertTrue($playoff->isComplete());
        $this->assertNotNull($playoff->getWinner());
        // With descending seeding and choosing participant1 each time, p1 should win
        $this->assertSame('p1', $playoff->getWinner()->getMonster()->__toString());
    }
}


