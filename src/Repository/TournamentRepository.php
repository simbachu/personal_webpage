<?php

declare(strict_types=1);

namespace App\Repository;

use App\Type\Tournament;
use App\Type\TournamentIdentifier;
use App\Type\TournamentParticipant;
use App\Type\MonsterIdentifier;
use PDO;
use InvalidArgumentException;
use RuntimeException;

//! @brief SQLite-based implementation of TournamentRepositoryInterface
//!
//! Persists tournament data to SQLite database with proper schema management.
//! Supports in-memory databases for testing.
final class TournamentRepository implements TournamentRepositoryInterface
{
    private ?PDO $pdo = null;

    //! @brief Construct repository with optional database path
    //! @param dbPath SQLite database path (null for in-memory database)
    public function __construct(?string $dbPath = null)
    {
        if ($dbPath === null) {
            // Use in-memory database for testing
            $this->pdo = new PDO('sqlite::memory:');
        } else {
            $this->pdo = $this->tryOpenSqlite($dbPath);
            if ($this->pdo === null) {
                throw new RuntimeException("Failed to open SQLite database at: $dbPath");
            }
        }
        
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->ensureSchema($this->pdo);
    }

    //! @brief Save a tournament
    //! @param tournament The tournament to save
    public function save(Tournament $tournament): void
    {
        $this->pdo->beginTransaction();
        try {
            // Save tournament metadata
            $stmt = $this->pdo->prepare('
                INSERT OR REPLACE INTO tournaments 
                (id, user_email, total_rounds, current_round, bracket_data, created_at, updated_at)
                VALUES (:id, :email, :total_rounds, :current_round, :bracket_data, :created_at, :updated_at)
            ');
            
            $now = time();
            $createdAt = $now; // In real implementation, might want to preserve original created_at
            $bracketData = $this->getBracketData($tournament);
            
            $stmt->execute([
                ':id' => $tournament->getId()->__toString(),
                ':email' => $tournament->getUserEmail(),
                ':total_rounds' => $tournament->getTotalRounds(),
                ':current_round' => $tournament->getCurrentRound(),
                ':bracket_data' => $bracketData,
                ':created_at' => $createdAt,
                ':updated_at' => $now,
            ]);

            // Delete existing participants (matches are preserved - we only add new matches)
            $this->pdo->prepare('DELETE FROM tournament_participants WHERE tournament_id = ?')
                ->execute([$tournament->getId()->__toString()]);

            // Save participants with their current stats
            $participantStmt = $this->pdo->prepare('
                INSERT INTO tournament_participants 
                (tournament_id, monster_identifier, score, wins, losses, draws)
                VALUES (:tournament_id, :monster, :score, :wins, :losses, :draws)
            ');

            foreach ($tournament->getParticipants() as $participant) {
                $participantStmt->execute([
                    ':tournament_id' => $tournament->getId()->__toString(),
                    ':monster' => $participant->getMonster()->__toString(),
                    ':score' => $participant->getScore(),
                    ':wins' => $participant->getWins(),
                    ':losses' => $participant->getLosses(),
                    ':draws' => $participant->getDraws(),
                ]);
            }

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw new RuntimeException("Failed to save tournament: " . $e->getMessage(), 0, $e);
        }
    }

    //! @brief Find a tournament by ID
    //! @param id The tournament identifier
    //! @return Tournament|null The tournament or null if not found
    public function findById(TournamentIdentifier $id): ?Tournament
    {
        $stmt = $this->pdo->prepare('
            SELECT id, user_email, total_rounds, current_round, bracket_data
            FROM tournaments
            WHERE id = ?
        ');
        $stmt->execute([$id->__toString()]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        // Load participants
        $participants = $this->loadParticipants($id->__toString());
        
        if (empty($participants)) {
            return null; // Tournament without participants is invalid
        }

        // Reconstruct tournament
        $tournament = new Tournament(
            $id,
            $row['user_email'],
            $participants,
            (int)$row['total_rounds']
        );

        // Restore current round using reflection since it's private
        // Note: This is a workaround - ideally Tournament would have a method to restore state
        $this->setTournamentCurrentRound($tournament, (int)$row['current_round']);

        return $tournament;
    }

    //! @brief Get bracket data from tournament (stored separately as JSON)
    //! @param tournament The tournament
    //! @return string|null JSON encoded bracket data or null
    private function getBracketData(Tournament $tournament): ?string
    {
        // Load existing bracket data if it exists
        $existing = $this->loadBracketData($tournament->getId());
        if ($existing !== null) {
            return json_encode($existing, JSON_THROW_ON_ERROR);
        }
        return null;
    }

    //! @brief Save bracket data for a tournament
    //! @param tournamentId Tournament identifier
    //! @param bracketData Bracket structure (will be JSON encoded)
    public function saveBracketData(TournamentIdentifier $tournamentId, array $bracketData): void
    {
        $json = json_encode($bracketData, JSON_THROW_ON_ERROR);
        $stmt = $this->pdo->prepare('
            UPDATE tournaments
            SET bracket_data = ?, updated_at = ?
            WHERE id = ?
        ');
        $stmt->execute([$json, time(), $tournamentId->__toString()]);
    }

    //! @brief Load bracket data for a tournament
    //! @param tournamentId Tournament identifier
    //! @return array|null Bracket structure or null if not found
    public function loadBracketData(TournamentIdentifier $tournamentId): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT bracket_data
            FROM tournaments
            WHERE id = ?
        ');
        $stmt->execute([$tournamentId->__toString()]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false || $row['bracket_data'] === null) {
            return null;
        }

        return json_decode($row['bracket_data'], true, 512, JSON_THROW_ON_ERROR);
    }

    //! @brief Find tournaments by user email
    //! @param userEmail The user's email
    //! @return array<Tournament> Array of tournaments for the user
    public function findByUserEmail(string $userEmail): array
    {
        $stmt = $this->pdo->prepare('
            SELECT id, user_email, total_rounds, current_round
            FROM tournaments
            WHERE user_email = ?
            ORDER BY created_at DESC
        ');
        $stmt->execute([$userEmail]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $tournaments = [];
        foreach ($rows as $row) {
            $id = TournamentIdentifier::fromString($row['id']);
            $participants = $this->loadParticipants($row['id']);
            
            if (empty($participants)) {
                continue; // Skip tournaments without participants
            }

            $tournament = new Tournament(
                $id,
                $row['user_email'],
                $participants,
                (int)$row['total_rounds']
            );

            $this->setTournamentCurrentRound($tournament, (int)$row['current_round']);
            $tournaments[] = $tournament;
        }

        return $tournaments;
    }

    //! @brief Delete a tournament
    //! @param id The tournament identifier
    public function delete(TournamentIdentifier $id): void
    {
        $this->pdo->beginTransaction();
        try {
            $tournamentIdStr = $id->__toString();
            $this->pdo->prepare('DELETE FROM tournament_matches WHERE tournament_id = ?')
                ->execute([$tournamentIdStr]);
            $this->pdo->prepare('DELETE FROM tournament_participants WHERE tournament_id = ?')
                ->execute([$tournamentIdStr]);
            $this->pdo->prepare('DELETE FROM tournaments WHERE id = ?')
                ->execute([$tournamentIdStr]);
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw new RuntimeException("Failed to delete tournament: " . $e->getMessage(), 0, $e);
        }
    }

    //! @brief Check if a tournament exists
    //! @param id The tournament identifier
    //! @return bool True if the tournament exists
    public function exists(TournamentIdentifier $id): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM tournaments WHERE id = ?');
        $stmt->execute([$id->__toString()]);
        return $stmt->fetch() !== false;
    }

    //! @brief Get all tournaments
    //! @return array<Tournament> All tournaments
    public function findAll(): array
    {
        $stmt = $this->pdo->query('
            SELECT id, user_email, total_rounds, current_round
            FROM tournaments
            ORDER BY created_at DESC
        ');
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $tournaments = [];
        foreach ($rows as $row) {
            $id = TournamentIdentifier::fromString($row['id']);
            $participants = $this->loadParticipants($row['id']);
            
            if (empty($participants)) {
                continue;
            }

            $tournament = new Tournament(
                $id,
                $row['user_email'],
                $participants,
                (int)$row['total_rounds']
            );

            $this->setTournamentCurrentRound($tournament, (int)$row['current_round']);
            $tournaments[] = $tournament;
        }

        return $tournaments;
    }

    //! @brief Save a match result to the database
    //! @param tournamentId Tournament identifier
    //! @param roundNumber Round number for this match
    //! @param participant1 First participant monster identifier
    //! @param participant2 Second participant monster identifier
    //! @param outcome Match outcome ('win', 'loss', 'draw')
    //! @param winner Winner monster identifier (null for draws)
    public function saveMatch(
        TournamentIdentifier $tournamentId,
        int $roundNumber,
        MonsterIdentifier $participant1,
        MonsterIdentifier $participant2,
        string $outcome,
        ?MonsterIdentifier $winner
    ): void {
        // Ensure participant1 < participant2 for consistent storage (avoid duplicates)
        $p1Str = $participant1->__toString();
        $p2Str = $participant2->__toString();
        if ($p1Str > $p2Str) {
            // Swap to ensure consistent ordering
            [$p1Str, $p2Str] = [$p2Str, $p1Str];
            // Also swap winner if applicable
            if ($winner !== null) {
                if ($winner->equals($participant1)) {
                    $winner = $participant2;
                } elseif ($winner->equals($participant2)) {
                    $winner = $participant1;
                }
            }
        }

        $winnerStr = $winner?->__toString();

        $stmt = $this->pdo->prepare('
            INSERT OR REPLACE INTO tournament_matches 
            (tournament_id, round_number, participant1, participant2, outcome, winner)
            VALUES (:tournament_id, :round_number, :participant1, :participant2, :outcome, :winner)
        ');
        
        $stmt->execute([
            ':tournament_id' => $tournamentId->__toString(),
            ':round_number' => $roundNumber,
            ':participant1' => $p1Str,
            ':participant2' => $p2Str,
            ':outcome' => $outcome,
            ':winner' => $winnerStr,
        ]);
    }

    //! @brief Load all matches for a tournament
    //! @param tournamentId Tournament identifier
    //! @return array<array{round:int,participant1:MonsterIdentifier,participant2:MonsterIdentifier,outcome:string,winner:MonsterIdentifier|null}> Array of match data
    public function loadMatches(TournamentIdentifier $tournamentId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT round_number, participant1, participant2, outcome, winner
            FROM tournament_matches
            WHERE tournament_id = ?
            ORDER BY round_number, participant1
        ');
        $stmt->execute([$tournamentId->__toString()]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $matches = [];
        foreach ($rows as $row) {
            $matches[] = [
                'round' => (int)$row['round_number'],
                'participant1' => MonsterIdentifier::fromString($row['participant1']),
                'participant2' => MonsterIdentifier::fromString($row['participant2']),
                'outcome' => $row['outcome'],
                'winner' => $row['winner'] ? MonsterIdentifier::fromString($row['winner']) : null,
            ];
        }

        return $matches;
    }

    //! @brief Load participants for a tournament
    //! @param tournamentId Tournament ID as string
    //! @return array<TournamentParticipant> Array of participants
    private function loadParticipants(string $tournamentId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT monster_identifier, score, wins, losses, draws
            FROM tournament_participants
            WHERE tournament_id = ?
            ORDER BY monster_identifier
        ');
        $stmt->execute([$tournamentId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $participants = [];
        foreach ($rows as $row) {
            $monster = MonsterIdentifier::fromString($row['monster_identifier']);
            $participant = new TournamentParticipant($monster, (int)$row['score']);
            
            // Restore stats using reflection since they're private
            $this->setParticipantStats(
                $participant,
                (int)$row['wins'],
                (int)$row['losses'],
                (int)$row['draws']
            );

            $participants[] = $participant;
        }

        return $participants;
    }

    //! @brief Set tournament current round using reflection
    //! @param tournament The tournament
    //! @param currentRound The current round number
    private function setTournamentCurrentRound(Tournament $tournament, int $currentRound): void
    {
        $reflection = new \ReflectionClass($tournament);
        $property = $reflection->getProperty('currentRound');
        $property->setAccessible(true);
        $property->setValue($tournament, $currentRound);
    }

    //! @brief Set participant statistics using reflection
    //! @param participant The participant
    //! @param wins Number of wins
    //! @param losses Number of losses
    //! @param draws Number of draws
    private function setParticipantStats(TournamentParticipant $participant, int $wins, int $losses, int $draws): void
    {
        $reflection = new \ReflectionClass($participant);
        
        $winsProp = $reflection->getProperty('wins');
        $winsProp->setAccessible(true);
        $winsProp->setValue($participant, $wins);
        
        $lossesProp = $reflection->getProperty('losses');
        $lossesProp->setAccessible(true);
        $lossesProp->setValue($participant, $losses);
        
        $drawsProp = $reflection->getProperty('draws');
        $drawsProp->setAccessible(true);
        $drawsProp->setValue($participant, $draws);
    }

    //! @brief Try to open SQLite database
    //! @param dbPath Database file path
    //! @return PDO|null PDO connection or null on failure
    private function tryOpenSqlite(string $dbPath): ?PDO
    {
        try {
            if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
                return null;
            }
            $dir = dirname($dbPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            return new PDO('sqlite:' . $dbPath);
        } catch (\Throwable $e) {
            return null;
        }
    }

    //! @brief Ensure database schema exists
    //! @param pdo PDO connection
    private function ensureSchema(PDO $pdo): void
    {
        $pdo->exec('
            CREATE TABLE IF NOT EXISTS tournaments (
                id TEXT PRIMARY KEY,
                user_email TEXT NOT NULL,
                total_rounds INTEGER NOT NULL,
                current_round INTEGER NOT NULL DEFAULT 0,
                bracket_data TEXT,
                created_at INTEGER NOT NULL,
                updated_at INTEGER NOT NULL
            )
        ');

        $pdo->exec('
            CREATE TABLE IF NOT EXISTS tournament_participants (
                tournament_id TEXT NOT NULL,
                monster_identifier TEXT NOT NULL,
                score INTEGER NOT NULL DEFAULT 0,
                wins INTEGER NOT NULL DEFAULT 0,
                losses INTEGER NOT NULL DEFAULT 0,
                draws INTEGER NOT NULL DEFAULT 0,
                PRIMARY KEY (tournament_id, monster_identifier),
                FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE
            )
        ');

        $pdo->exec('
            CREATE TABLE IF NOT EXISTS tournament_matches (
                tournament_id TEXT NOT NULL,
                round_number INTEGER NOT NULL,
                participant1 TEXT NOT NULL,
                participant2 TEXT NOT NULL,
                outcome TEXT NOT NULL,
                winner TEXT,
                PRIMARY KEY (tournament_id, round_number, participant1, participant2),
                FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE
            )
        ');

        // Create indexes for performance
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_tournaments_user_email ON tournaments(user_email)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_participants_tournament ON tournament_participants(tournament_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_matches_tournament ON tournament_matches(tournament_id)');
    }
}

