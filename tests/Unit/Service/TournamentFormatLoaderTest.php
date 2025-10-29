<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Service\TournamentFormatLoader;
use App\Service\PlayoffBracketFactory;
use App\Type\MonsterIdentifier;
use App\Type\TournamentParticipant;
use App\Type\SingleEliminationBracket;
use App\Type\DoubleEliminationBracket;

final class TournamentFormatLoaderTest extends TestCase
{
    //! @brief Load example config and validate fields
    public function test_load_example_config(): void
    {
        //! @section Arrange
        $loader = new TournamentFormatLoader();
        $path = __DIR__ . '/../../..' . '/example_config.yaml';

        //! @section Act
        $fmt = $loader->load($path, 'favorite-pokemon');

        //! @section Assert
        $this->assertSame('swiss-tournament', $fmt->format);
        $this->assertSame('double-elimination', $fmt->playoff);
        $this->assertSame(16, $fmt->playoffCutoff);
        $this->assertFalse($fmt->playoffReset);
    }

    //! @brief Build playoff bracket according to loaded config
    public function test_build_playoff_from_config(): void
    {
        //! @section Arrange
        $loader = new TournamentFormatLoader();
        $factory = new PlayoffBracketFactory();
        $path = __DIR__ . '/../../..' . '/example_config.yaml';
        $fmt = $loader->load($path, 'favorite-pokemon');

        // 32 participants and mock standings descending p1..p32
        $participants = [];
        $standings = [];
        for ($i = 1; $i <= 32; $i++) {
            $name = 'p' . $i;
            $participants[] = new TournamentParticipant(MonsterIdentifier::fromString($name));
            $standings[$name] = 33 - $i;
        }

        //! @section Act
        $bracket = $factory->createFromStandings(
            $participants,
            $standings,
            $fmt->playoffCutoff ?? 16,
            $fmt->playoff ?? 'double-elimination',
            $fmt->playoffReset
        );

        //! @section Assert
        $this->assertInstanceOf(DoubleEliminationBracket::class, $bracket);
        $round1 = $bracket->getCurrentRoundMatches();
        $this->assertCount(8, $round1);
        $this->assertSame('p1', $round1[0]->getParticipant1()->getMonster()->__toString());
        $this->assertSame('p16', $round1[0]->getParticipant2()->getMonster()->__toString());
    }
}


