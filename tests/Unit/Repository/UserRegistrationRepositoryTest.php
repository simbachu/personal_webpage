<?php

declare(strict_types=1);

namespace Tests\Unit\Repository;

use PHPUnit\Framework\TestCase;
use App\Repository\UserRegistrationRepository;

//! @brief Unit tests for UserRegistrationRepository
//!
//! Tests CRUD operations, expiration handling, verification flow, and cleanup.
final class UserRegistrationRepositoryTest extends TestCase
{
    private UserRegistrationRepository $repository;

    //! @brief Set up test fixtures
    protected function setUp(): void
    {
        $this->repository = new UserRegistrationRepository(); // In-memory database
    }

    //! @brief Test storing pending registration
    public function test_store_pending_registration(): void
    {
        //! @section Arrange
        $email = 'user@example.com';
        $secret = 'ABCD1234';
        $expiresAt = time() + 86400; // 24 hours

        //! @section Act
        $this->repository->storePendingRegistration($email, $secret, $expiresAt);

        //! @section Assert
        $registration = $this->repository->findPendingRegistration($email);
        $this->assertNotNull($registration);
        $this->assertSame($secret, $registration['secret']);
        $this->assertSame($expiresAt, $registration['expires_at']);
    }

    //! @brief Test finding pending registration returns null when not found
    public function test_find_pending_registration_returns_null_when_not_found(): void
    {
        //! @section Act
        $registration = $this->repository->findPendingRegistration('nonexistent@example.com');

        //! @section Assert
        $this->assertNull($registration);
    }

    //! @brief Test finding pending registration excludes verified ones
    public function test_find_pending_registration_excludes_verified(): void
    {
        //! @section Arrange
        $email = 'user@example.com';
        $secret = 'ABCD1234';
        $expiresAt = time() + 86400;
        
        $this->repository->storePendingRegistration($email, $secret, $expiresAt);
        $this->repository->verifyAndRegister($email, $secret);

        //! @section Act
        $registration = $this->repository->findPendingRegistration($email);

        //! @section Assert
        $this->assertNull($registration, 'Verified registrations should not be found as pending');
    }

    //! @brief Test verification succeeds with valid secret
    public function test_verify_and_register_succeeds_with_valid_secret(): void
    {
        //! @section Arrange
        $email = 'user@example.com';
        $secret = 'ABCD1234';
        $expiresAt = time() + 86400;
        
        $this->repository->storePendingRegistration($email, $secret, $expiresAt);

        //! @section Act
        $result = $this->repository->verifyAndRegister($email, $secret);

        //! @section Assert
        $this->assertTrue($result);
        $this->assertTrue($this->repository->isRegistered($email));
    }

    //! @brief Test verification fails with invalid secret
    public function test_verify_and_register_fails_with_invalid_secret(): void
    {
        //! @section Arrange
        $email = 'user@example.com';
        $secret = 'ABCD1234';
        $wrongSecret = 'WRONG12';
        $expiresAt = time() + 86400;
        
        $this->repository->storePendingRegistration($email, $secret, $expiresAt);

        //! @section Act
        $result = $this->repository->verifyAndRegister($email, $wrongSecret);

        //! @section Assert
        $this->assertFalse($result);
        $this->assertFalse($this->repository->isRegistered($email));
    }

    //! @brief Test verification fails with expired secret
    public function test_verify_and_register_fails_with_expired_secret(): void
    {
        //! @section Arrange
        $email = 'user@example.com';
        $secret = 'ABCD1234';
        $expiresAt = time() - 1; // Expired
        
        $this->repository->storePendingRegistration($email, $secret, $expiresAt);

        //! @section Act
        $result = $this->repository->verifyAndRegister($email, $secret);

        //! @section Assert
        $this->assertFalse($result);
        $this->assertFalse($this->repository->isRegistered($email));
    }

    //! @brief Test verification fails when no pending registration exists
    public function test_verify_and_register_fails_when_no_pending_registration(): void
    {
        //! @section Act
        $result = $this->repository->verifyAndRegister('user@example.com', 'ABCD1234');

        //! @section Assert
        $this->assertFalse($result);
    }

    //! @brief Test isRegistered returns true for verified email
    public function test_is_registered_returns_true_for_verified_email(): void
    {
        //! @section Arrange
        $email = 'user@example.com';
        $secret = 'ABCD1234';
        $expiresAt = time() + 86400;
        
        $this->repository->storePendingRegistration($email, $secret, $expiresAt);
        $this->repository->verifyAndRegister($email, $secret);

        //! @section Act
        $result = $this->repository->isRegistered($email);

        //! @section Assert
        $this->assertTrue($result);
    }

    //! @brief Test isRegistered returns false for unverified email
    public function test_is_registered_returns_false_for_unverified_email(): void
    {
        //! @section Act
        $result = $this->repository->isRegistered('user@example.com');

        //! @section Assert
        $this->assertFalse($result);
    }

    //! @brief Test deletePendingRegistration removes pending registration
    public function test_delete_pending_registration(): void
    {
        //! @section Arrange
        $email = 'user@example.com';
        $secret = 'ABCD1234';
        $expiresAt = time() + 86400;
        
        $this->repository->storePendingRegistration($email, $secret, $expiresAt);

        //! @section Act
        $this->repository->deletePendingRegistration($email);

        //! @section Assert
        $registration = $this->repository->findPendingRegistration($email);
        $this->assertNull($registration);
    }

    //! @brief Test cleanupExpiredRegistrations removes expired registrations
    public function test_cleanup_expired_registrations(): void
    {
        //! @section Arrange
        $expired1 = 'expired1@example.com';
        $expired2 = 'expired2@example.com';
        $valid = 'valid@example.com';
        
        $this->repository->storePendingRegistration($expired1, 'SECRET1', time() - 100);
        $this->repository->storePendingRegistration($expired2, 'SECRET2', time() - 50);
        $this->repository->storePendingRegistration($valid, 'SECRET3', time() + 86400);

        //! @section Act
        $deleted = $this->repository->cleanupExpiredRegistrations();

        //! @section Assert
        $this->assertSame(2, $deleted);
        $this->assertNull($this->repository->findPendingRegistration($expired1));
        $this->assertNull($this->repository->findPendingRegistration($expired2));
        $this->assertNotNull($this->repository->findPendingRegistration($valid));
    }

    //! @brief Test storing registration replaces existing pending registration
    public function test_store_pending_registration_replaces_existing(): void
    {
        //! @section Arrange
        $email = 'user@example.com';
        $secret1 = 'ABCD1234';
        $secret2 = 'EFGH5678';
        $expiresAt = time() + 86400;
        
        $this->repository->storePendingRegistration($email, $secret1, $expiresAt);

        //! @section Act
        $this->repository->storePendingRegistration($email, $secret2, $expiresAt);

        //! @section Assert
        $registration = $this->repository->findPendingRegistration($email);
        $this->assertNotNull($registration);
        $this->assertSame($secret2, $registration['secret']);
    }

    //! @brief Test verification persists across repository instances (database persistence)
    public function test_verification_persists_across_instances(): void
    {
        //! @section Arrange
        $dbPath = sys_get_temp_dir() . '/test_user_reg_' . uniqid() . '.sqlite';
        $repo1 = new UserRegistrationRepository($dbPath);
        
        $email = 'user@example.com';
        $secret = 'ABCD1234';
        $expiresAt = time() + 86400;
        
        $repo1->storePendingRegistration($email, $secret, $expiresAt);
        $repo1->verifyAndRegister($email, $secret);
        unset($repo1); // Close connection

        //! @section Act
        $repo2 = new UserRegistrationRepository($dbPath);
        $isRegistered = $repo2->isRegistered($email);

        //! @section Assert
        $this->assertTrue($isRegistered);
        
        //! Cleanup
        @unlink($dbPath);
    }
}

