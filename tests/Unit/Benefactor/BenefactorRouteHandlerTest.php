<?php

declare(strict_types=1);

namespace Tests\Unit\Benefactor;

use App\Benefactor\BenefactorRequest;
use App\Benefactor\BenefactorRouteHandler;
use App\Benefactor\BenefactorSession;
use App\Benefactor\MemberCache;
use App\Benefactor\MemberMarkup;
use App\Benefactor\OAuth\PatreonOAuthClient;
use App\Benefactor\PatreonMemberService;
use App\Shared\Http\HttpStatusCode;
use App\Shared\Http\Route;
use App\Shared\Http\TemplateName;
use App\Shared\Support\FilePath;
use PHPUnit\Framework\TestCase;

//! @brief BenefactorRouteHandler OAuth login, callback, and cached member list
final class BenefactorRouteHandlerTest extends TestCase
{
    private const PAGE_URL = 'https://simbachu.com/benefactor';

    private string $cacheDir;
    private array $sessionData;
    /** @var list<array{method: string, url: string}> */
    private array $httpCalls;

    protected function setUp(): void
    {
        $this->cacheDir = sys_get_temp_dir() . '/benefactor_handler_' . uniqid();
        mkdir($this->cacheDir, 0777, true);
        $this->sessionData = [];
        $this->httpCalls = [];
    }

    protected function tearDown(): void
    {
        foreach (glob($this->cacheDir . '/*') ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        if (is_dir($this->cacheDir)) {
            rmdir($this->cacheDir);
        }
    }

    public function test_unconfigured_credentials_show_error_without_login_link(): void
    {
        //! @section Arrange
        $handler = $this->handler(clientId: '', clientSecret: '');

        //! @section Act
        $result = $handler->handle($this->route());

        //! @section Assert
        $this->assertSame(HttpStatusCode::OK, $result->getStatusCode());
        $this->assertNull($result->getRedirectUrl());
        $data = $result->getData();
        $this->assertFalse($data['configured']);
        $this->assertNull($data['authorize_url']);
        $this->assertSame('Patreon OAuth is not configured.', $data['error']);
    }

    public function test_login_view_sets_state_and_authorize_url(): void
    {
        //! @section Arrange
        $handler = $this->handler(stateGenerator: fn (): string => 'fixed-state');

        //! @section Act
        $result = $handler->handle($this->route());

        //! @section Assert
        $data = $result->getData();
        $this->assertTrue($data['configured']);
        $this->assertFalse($data['logged_in']);
        $this->assertNotNull($data['authorize_url']);
        $this->assertStringContainsString('state=fixed-state', $data['authorize_url']);
        $this->assertStringContainsString('client_id=cid', $data['authorize_url']);
        $this->assertSame('fixed-state', $this->sessionData['benefactor_oauth_state'] ?? null);
        $this->assertSame([], $this->httpCalls);
    }

    public function test_callback_success_redirects_to_page(): void
    {
        //! @section Arrange
        $this->sessionData['benefactor_oauth_state'] = 'csrf-state';
        $handler = $this->handler(request: new BenefactorRequest('auth-code', 'csrf-state'));

        //! @section Act
        $result = $handler->handle($this->route());

        //! @section Assert
        $this->assertSame(HttpStatusCode::FOUND, $result->getStatusCode());
        $this->assertSame(self::PAGE_URL, $result->getRedirectUrl());
        $this->assertSame('tok-abc', $this->sessionData['benefactor_access_token'] ?? null);
        $this->assertSame('user-1', $this->sessionData['benefactor_user_id'] ?? null);
        $this->assertArrayNotHasKey('benefactor_oauth_state', $this->sessionData);
    }

    public function test_second_visit_within_ttl_does_not_call_members_api(): void
    {
        //! @section Arrange
        $this->sessionData['benefactor_oauth_state'] = 'csrf-state';
        $this->handler(request: new BenefactorRequest('auth-code', 'csrf-state'))
            ->handle($this->route());
        $this->httpCalls = [];

        //! @section Act
        $result = $this->handler()->handle($this->route());

        //! @section Assert
        $this->assertSame(HttpStatusCode::OK, $result->getStatusCode());
        $this->assertTrue($result->getData()['logged_in']);
        $this->assertSame(
            '<span class="hardy-club">Epoint Man</span>',
            $result->getData()['markup']
        );
        $this->assertSame([], $this->httpCalls);
    }

    public function test_bad_oauth_state_returns_error(): void
    {
        //! @section Arrange
        $this->sessionData['benefactor_oauth_state'] = 'expected';
        $handler = $this->handler(request: new BenefactorRequest('auth-code', 'wrong'));

        //! @section Act
        $result = $handler->handle($this->route());

        //! @section Assert
        $this->assertSame(HttpStatusCode::OK, $result->getStatusCode());
        $this->assertNull($result->getRedirectUrl());
        $this->assertSame('Invalid OAuth state.', $result->getData()['error']);
        $this->assertNotNull($result->getData()['authorize_url']);
        $this->assertSame([], $this->httpCalls);
    }

    public function test_oauth_error_query_is_shown(): void
    {
        //! @section Arrange
        $handler = $this->handler(request: new BenefactorRequest(error: 'access_denied'));

        //! @section Act
        $result = $handler->handle($this->route());

        //! @section Assert
        $this->assertSame('access_denied', $result->getData()['error']);
        $this->assertFalse($result->getData()['logged_in']);
        $this->assertNotNull($result->getData()['authorize_url']);
        $this->assertSame([], $this->httpCalls);
    }

    private function route(): Route
    {
        return new Route('/benefactor', TemplateName::BENEFACTOR, [
            'title' => 'Benefactor',
        ]);
    }

    private function handler(
        string $clientId = 'cid',
        string $clientSecret = 'secret',
        ?BenefactorRequest $request = null,
        ?callable $stateGenerator = null
    ): BenefactorRouteHandler {
        $http = function (string $method, string $url): string {
            $this->httpCalls[] = compact('method', 'url');
            if ($method === 'POST') {
                return json_encode(['access_token' => 'tok-abc']);
            }
            if (str_contains($url, '/identity')) {
                return json_encode([
                    'data' => [
                        'id' => 'user-1',
                        'type' => 'user',
                        'relationships' => [
                            'campaign' => ['data' => ['id' => 'camp-1', 'type' => 'campaign']],
                        ],
                    ],
                ]);
            }
            return json_encode([
                'data' => [[
                    'id' => 'mem-1',
                    'type' => 'member',
                    'attributes' => [
                        'full_name' => 'Epoint Man',
                        'patron_status' => 'active_patron',
                    ],
                    'relationships' => [
                        'currently_entitled_tiers' => [
                            'data' => [['id' => 'tier-hardy', 'type' => 'tier']],
                        ],
                    ],
                ]],
                'included' => [[
                    'id' => 'tier-hardy',
                    'type' => 'tier',
                    'attributes' => ['title' => 'Hardy Club', 'amount_cents' => 500],
                ]],
            ]);
        };

        return new BenefactorRouteHandler(
            new PatreonOAuthClient($clientId, $clientSecret, $http),
            new MemberCache(
                new PatreonMemberService($http),
                FilePath::fromString($this->cacheDir)
            ),
            new MemberMarkup(),
            new BenefactorSession($this->sessionData),
            self::PAGE_URL,
            $request ?? new BenefactorRequest(),
            $stateGenerator
        );
    }
}
