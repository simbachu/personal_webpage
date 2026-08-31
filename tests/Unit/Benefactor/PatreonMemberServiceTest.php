<?php

declare(strict_types=1);

namespace Tests\Unit\Benefactor;

use App\Benefactor\PatreonMemberService;
use App\Benefactor\PatronMember;
use PHPUnit\Framework\TestCase;

//! @brief PatreonMemberService maps identity and paginated members
final class PatreonMemberServiceTest extends TestCase
{
    public function test_fetch_identity_returns_user_and_campaign(): void
    {
        //! @section Arrange
        $service = new PatreonMemberService($this->httpReturning([
            json_encode([
                'data' => [
                    'id' => 'user-1',
                    'type' => 'user',
                    'relationships' => [
                        'campaign' => ['data' => ['id' => 'camp-1', 'type' => 'campaign']],
                    ],
                ],
            ]),
        ]));

        //! @section Act
        $result = $service->fetchIdentity('tok');

        //! @section Assert
        $this->assertTrue($result->isSuccess());
        $identity = $result->getValue();
        $this->assertSame('user-1', $identity['userId']);
        $this->assertSame('camp-1', $identity['campaignId']);
    }

    public function test_fetch_identity_without_campaign_fails(): void
    {
        //! @section Arrange
        $service = new PatreonMemberService($this->httpReturning([
            json_encode(['data' => ['id' => 'user-1', 'type' => 'user']]),
        ]));

        //! @section Act
        $result = $service->fetchIdentity('tok');

        //! @section Assert
        $this->assertTrue($result->isFailure());
        $this->assertSame('No Patreon campaign is linked to this account.', $result->getError());
    }

    public function test_fetch_members_maps_tiers_and_follows_pagination(): void
    {
        //! @section Arrange
        $page1 = json_encode([
            'data' => [
                [
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
                ],
            ],
            'included' => [
                [
                    'id' => 'tier-hardy',
                    'type' => 'tier',
                    'attributes' => ['title' => 'Hardy Club', 'amount_cents' => 500],
                ],
            ],
            'links' => [
                'next' => 'https://www.patreon.com/api/oauth2/v2/campaigns/camp-1/members?page=2',
            ],
        ]);
        $page2 = json_encode([
            'data' => [
                [
                    'id' => 'mem-2',
                    'type' => 'member',
                    'attributes' => [
                        'full_name' => 'Question Man',
                        'patron_status' => 'active_patron',
                    ],
                    'relationships' => [
                        'currently_entitled_tiers' => [
                            'data' => [['id' => 'tier-euclid', 'type' => 'tier']],
                        ],
                    ],
                ],
            ],
            'included' => [
                [
                    'id' => 'tier-euclid',
                    'type' => 'tier',
                    'attributes' => ['title' => 'Euclid Club', 'amount_cents' => 1000],
                ],
            ],
        ]);

        $urls = [];
        $service = new PatreonMemberService(
            function (string $method, string $url) use (&$urls, $page1, $page2): string {
                $this->assertSame('GET', $method);
                $urls[] = $url;
                if (str_contains($url, 'page=2')) {
                    return $page2;
                }
                return $page1;
            }
        );

        //! @section Act
        $result = $service->fetchMembers('tok', 'camp-1');

        //! @section Assert
        $this->assertTrue($result->isSuccess());
        $members = $result->getValue();
        $this->assertCount(2, $members);
        $this->assertInstanceOf(PatronMember::class, $members[0]);
        $this->assertSame('Epoint Man', $members[0]->fullName);
        $this->assertSame('Hardy Club', $members[0]->tiers[0]->title);
        $this->assertSame(500, $members[0]->tiers[0]->amountCents);
        $this->assertSame('Question Man', $members[1]->fullName);
        $this->assertCount(2, $urls);
        $this->assertStringContainsString('/campaigns/camp-1/members', $urls[0]);
        $this->assertStringContainsString('currently_entitled_tiers', $urls[0]);
        $this->assertSame(
            'https://www.patreon.com/api/oauth2/v2/campaigns/camp-1/members?page=2',
            $urls[1]
        );
    }

    public function test_identity_sends_bearer_token(): void
    {
        //! @section Arrange
        $headersSeen = [];
        $service = new PatreonMemberService(
            function (string $method, string $url, array $headers) use (&$headersSeen): string {
                $headersSeen = $headers;
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
        );

        //! @section Act
        $service->fetchIdentity('tok-secret');

        //! @section Assert
        $this->assertSame('Bearer tok-secret', $headersSeen['Authorization'] ?? null);
    }

    //! @param list<string> $responses
    //! @return callable(string, string, array<string, string>, ?string): string
    private function httpReturning(array $responses): callable
    {
        $callCount = 0;
        return function () use ($responses, &$callCount): string {
            $response = $responses[$callCount] ?? throw new \RuntimeException('Unexpected HTTP call');
            $callCount++;
            return $response;
        };
    }
}
