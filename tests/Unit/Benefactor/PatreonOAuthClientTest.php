<?php

declare(strict_types=1);

namespace Tests\Unit\Benefactor;

use App\Benefactor\OAuth\PatreonOAuthClient;
use PHPUnit\Framework\TestCase;

//! @brief PatreonOAuthClient builds authorize URLs and exchanges codes
final class PatreonOAuthClientTest extends TestCase
{
    public function test_is_configured_when_credentials_present(): void
    {
        //! @section Arrange
        $client = new PatreonOAuthClient('cid', 'secret', $this->unusedHttp());

        //! @section Assert
        $this->assertTrue($client->isConfigured());
    }

    public function test_is_not_configured_when_credentials_missing(): void
    {
        //! @section Arrange
        $client = new PatreonOAuthClient('', '', $this->unusedHttp());

        //! @section Assert
        $this->assertFalse($client->isConfigured());
    }

    public function test_authorize_url_includes_client_redirect_scope_and_state(): void
    {
        //! @section Arrange
        $client = new PatreonOAuthClient('cid-123', 'secret', $this->unusedHttp());

        //! @section Act
        $url = $client->authorizeUrl('https://simbachu.com/benefactor', 'csrf-state');

        //! @section Assert
        $this->assertStringStartsWith('https://www.patreon.com/oauth2/authorize?', $url);
        $parts = parse_url($url);
        parse_str($parts['query'] ?? '', $query);
        $this->assertSame('code', $query['response_type']);
        $this->assertSame('cid-123', $query['client_id']);
        $this->assertSame('https://simbachu.com/benefactor', $query['redirect_uri']);
        $this->assertSame('csrf-state', $query['state']);
        $this->assertSame('identity campaigns campaigns.members', $query['scope']);
    }

    public function test_exchanges_code_for_access_token(): void
    {
        //! @section Arrange
        $calls = [];
        $client = new PatreonOAuthClient(
            'cid',
            'secret',
            function (string $method, string $url, array $headers, ?string $body) use (&$calls): string {
                $calls[] = compact('method', 'url', 'headers', 'body');
                return json_encode(['access_token' => 'tok-abc', 'refresh_token' => 'ref']);
            }
        );

        //! @section Act
        $result = $client->exchangeCode('auth-code', 'https://simbachu.com/benefactor');

        //! @section Assert
        $this->assertTrue($result->isSuccess());
        $this->assertSame('tok-abc', $result->getValue());
        $this->assertCount(1, $calls);
        $this->assertSame('POST', $calls[0]['method']);
        $this->assertSame('https://www.patreon.com/api/oauth2/token', $calls[0]['url']);
        parse_str($calls[0]['body'] ?? '', $posted);
        $this->assertSame('authorization_code', $posted['grant_type']);
        $this->assertSame('auth-code', $posted['code']);
        $this->assertSame('cid', $posted['client_id']);
        $this->assertSame('secret', $posted['client_secret']);
        $this->assertSame('https://simbachu.com/benefactor', $posted['redirect_uri']);
    }

    public function test_exchange_failure_returns_error(): void
    {
        //! @section Arrange
        $client = new PatreonOAuthClient(
            'cid',
            'secret',
            fn () => json_encode(['error' => 'invalid_grant', 'error_description' => 'Code expired'])
        );

        //! @section Act
        $result = $client->exchangeCode('stale', 'https://simbachu.com/benefactor');

        //! @section Assert
        $this->assertTrue($result->isFailure());
        $this->assertSame('Code expired', $result->getError());
    }

    //! @return callable(string, string, array<string, string>, ?string): string
    private function unusedHttp(): callable
    {
        return function (): string {
            throw new \RuntimeException('HTTP should not be called');
        };
    }
}
