<?php

declare(strict_types=1);

namespace App\Benefactor;

//! @brief Session keys for Benefactor OAuth (CSRF state, token, user id)
final class BenefactorSession
{
    private const STATE_KEY = 'benefactor_oauth_state';
    private const TOKEN_KEY = 'benefactor_access_token';
    private const USER_KEY = 'benefactor_user_id';

    //! @brief Wrap a session array (real $_SESSION or a test array)
    //! @param storage Session storage by reference
    public function __construct(
        private array &$storage
    ) {
    }

    //! @brief Get the pending CSRF state
    //! @return string|null
    public function getState(): ?string
    {
        return $this->stringOrNull(self::STATE_KEY);
    }

    //! @brief Store CSRF state
    //! @param state Random state string
    public function setState(string $state): void
    {
        $this->storage[self::STATE_KEY] = $state;
    }

    //! @brief Get the access token
    //! @return string|null
    public function getAccessToken(): ?string
    {
        return $this->stringOrNull(self::TOKEN_KEY);
    }

    //! @brief Store the access token
    //! @param token OAuth access token
    public function setAccessToken(string $token): void
    {
        $this->storage[self::TOKEN_KEY] = $token;
    }

    //! @brief Get the Patreon user id
    //! @return string|null
    public function getUserId(): ?string
    {
        return $this->stringOrNull(self::USER_KEY);
    }

    //! @brief Store the Patreon user id
    //! @param userId Patreon user id
    public function setUserId(string $userId): void
    {
        $this->storage[self::USER_KEY] = $userId;
    }

    //! @brief Drop CSRF state after it has been consumed
    public function clearState(): void
    {
        unset($this->storage[self::STATE_KEY]);
    }

    //! @brief Read a string session value
    //! @param key Storage key
    //! @return string|null
    private function stringOrNull(string $key): ?string
    {
        $value = $this->storage[$key] ?? null;
        return is_string($value) && $value !== '' ? $value : null;
    }
}
