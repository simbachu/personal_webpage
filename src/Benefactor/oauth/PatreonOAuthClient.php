<?php

declare(strict_types=1);

namespace App\Benefactor\OAuth;

use App\Shared\Support\Result;

//! @brief Patreon OAuth 2 authorize URL and authorization-code exchange
final class PatreonOAuthClient
{
    public const AUTHORIZE_URL = 'https://www.patreon.com/oauth2/authorize';
    public const TOKEN_URL = 'https://www.patreon.com/api/oauth2/token';
    public const SCOPES = 'identity campaigns campaigns.members';

    //! @brief Construct the OAuth client
    //! @param clientId Patreon client id
    //! @param clientSecret Patreon client secret
    //! @param httpClient Callable (method, url, headers, body) returning raw response body
    public function __construct(
        private readonly string $clientId,
        private readonly string $clientSecret,
        private $httpClient
    ) {
    }

    //! @brief Whether client id and secret are present
    //! @return bool
    public function isConfigured(): bool
    {
        return $this->clientId !== '' && $this->clientSecret !== '';
    }

    //! @brief Build the Patreon authorization URL
    //! @param redirectUri Pre-registered redirect URI
    //! @param state CSRF state
    //! @return string
    public function authorizeUrl(string $redirectUri, string $state): string
    {
        return self::AUTHORIZE_URL . '?' . http_build_query([
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $redirectUri,
            'scope' => self::SCOPES,
            'state' => $state,
        ]);
    }

    //! @brief Exchange an authorization code for an access token
    //! @param code OAuth authorization code
    //! @param redirectUri Same redirect URI used to start the flow
    //! @return Result<string> Access token on success
    public function exchangeCode(string $code, string $redirectUri): Result
    {
        $body = http_build_query([
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $redirectUri,
        ]);

        try {
            $raw = ($this->httpClient)(
                'POST',
                self::TOKEN_URL,
                ['Content-Type' => 'application/x-www-form-urlencoded'],
                $body
            );
        } catch (\Throwable $exception) {
            return Result::failure($exception->getMessage());
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return Result::failure('Patreon token response was not valid JSON.');
        }

        if (isset($decoded['access_token']) && is_string($decoded['access_token']) && $decoded['access_token'] !== '') {
            return Result::success($decoded['access_token']);
        }

        $error = $decoded['error_description'] ?? $decoded['error'] ?? 'Token exchange failed.';
        return Result::failure(is_string($error) ? $error : 'Token exchange failed.');
    }
}
