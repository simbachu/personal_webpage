<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;
use RuntimeException;

//! @brief SQLite-based implementation of UserRegistrationRepositoryInterface
//!
//! Persists user registration data to SQLite database with secret codes
//! and expiration handling.
final class UserRegistrationRepository implements UserRegistrationRepositoryInterface
{
    private ?PDO $pdo = null;

    //! @brief Construct repository with optional database path
    //! @param dbPath SQLite database path (null for in-memory database, or reuse tournament DB)
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

    //! @brief Store a pending registration (email + secret)
    //! @param email User's email address
    //! @param secret Secret verification code
    //! @param expiresAt Unix timestamp when secret expires
    public function storePendingRegistration(string $email, string $secret, int $expiresAt): void
    {
        $stmt = $this->pdo->prepare('
            INSERT OR REPLACE INTO user_registrations 
            (email, secret, expires_at, created_at)
            VALUES (:email, :secret, :expires_at, :created_at)
        ');
        
        $stmt->execute([
            ':email' => $email,
            ':secret' => $secret,
            ':expires_at' => $expiresAt,
            ':created_at' => time(),
        ]);
    }

    //! @brief Find pending registration by email
    //! @param email User's email address
    //! @return array{secret:string,expires_at:int}|null Registration data or null if not found
    public function findPendingRegistration(string $email): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT secret, expires_at
            FROM user_registrations
            WHERE email = ? AND is_verified = 0
        ');
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return [
            'secret' => $row['secret'],
            'expires_at' => (int)$row['expires_at'],
        ];
    }

    //! @brief Verify and register email with secret code
    //! @param email User's email address
    //! @param secret Secret verification code
    //! @return bool True if verification successful and email registered
    public function verifyAndRegister(string $email, string $secret): bool
    {
        $stmt = $this->pdo->prepare('
            SELECT expires_at
            FROM user_registrations
            WHERE email = ? AND secret = ? AND is_verified = 0
        ');
        $stmt->execute([$email, $secret]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return false; // No matching pending registration
        }

        $expiresAt = (int)$row['expires_at'];
        if ($expiresAt < time()) {
            return false; // Secret has expired
        }

        // Mark as verified and registered
        $updateStmt = $this->pdo->prepare('
            UPDATE user_registrations
            SET is_verified = 1, verified_at = ?
            WHERE email = ? AND secret = ?
        ');
        $updateStmt->execute([time(), $email, $secret]);

        return true;
    }

    //! @brief Check if email is already registered
    //! @param email User's email address
    //! @return bool True if email is registered
    public function isRegistered(string $email): bool
    {
        $stmt = $this->pdo->prepare('
            SELECT 1 FROM user_registrations
            WHERE email = ? AND is_verified = 1
        ');
        $stmt->execute([$email]);
        return $stmt->fetch() !== false;
    }

    //! @brief Delete pending registration (cleanup expired or used)
    //! @param email User's email address
    public function deletePendingRegistration(string $email): void
    {
        $this->pdo->prepare('DELETE FROM user_registrations WHERE email = ? AND is_verified = 0')
            ->execute([$email]);
    }

    //! @brief Delete expired pending registrations
    //! @return int Number of deleted registrations
    public function cleanupExpiredRegistrations(): int
    {
        $stmt = $this->pdo->prepare('
            DELETE FROM user_registrations
            WHERE is_verified = 0 AND expires_at < ?
        ');
        $stmt->execute([time()]);
        return $stmt->rowCount();
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
            CREATE TABLE IF NOT EXISTS user_registrations (
                email TEXT PRIMARY KEY,
                secret TEXT NOT NULL,
                expires_at INTEGER NOT NULL,
                is_verified INTEGER NOT NULL DEFAULT 0,
                created_at INTEGER NOT NULL,
                verified_at INTEGER
            )
        ');

        // Create indexes for performance
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_registrations_expires ON user_registrations(expires_at)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_registrations_verified ON user_registrations(is_verified)');
    }
}

