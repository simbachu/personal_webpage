<?php

declare(strict_types=1);

namespace App\Repository;

//! @brief Interface for user registration persistence operations
//!
//! Defines the contract for storing and retrieving user registration data
//! including email addresses, secret codes, and verification status.
interface UserRegistrationRepositoryInterface
{
    //! @brief Store a pending registration (email + secret)
    //! @param email User's email address
    //! @param secret Secret verification code
    //! @param expiresAt Unix timestamp when secret expires
    public function storePendingRegistration(string $email, string $secret, int $expiresAt): void;

    //! @brief Find pending registration by email
    //! @param email User's email address
    //! @return array{secret:string,expires_at:int}|null Registration data or null if not found
    public function findPendingRegistration(string $email): ?array;

    //! @brief Verify and register email with secret code
    //! @param email User's email address
    //! @param secret Secret verification code
    //! @return bool True if verification successful and email registered
    public function verifyAndRegister(string $email, string $secret): bool;

    //! @brief Check if email is already registered
    //! @param email User's email address
    //! @return bool True if email is registered
    public function isRegistered(string $email): bool;

    //! @brief Delete pending registration (cleanup expired or used)
    //! @param email User's email address
    public function deletePendingRegistration(string $email): void;

    //! @brief Delete expired pending registrations
    //! @return int Number of deleted registrations
    public function cleanupExpiredRegistrations(): int;
}

