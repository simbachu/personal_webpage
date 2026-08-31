<?php

declare(strict_types=1);

namespace Tests\Unit\Benefactor;

use App\Benefactor\MemberCache;
use App\Benefactor\PatreonMemberService;
use App\Shared\Support\FilePath;
use PHPUnit\Framework\TestCase;

//! @brief MemberCache keeps a 30-minute member list per Patreon user
final class MemberCacheTest extends TestCase
{
    private string $cacheDir;

    protected function setUp(): void
    {
        $this->cacheDir = sys_get_temp_dir() . '/benefactor_cache_' . uniqid();
        mkdir($this->cacheDir, 0777, true);
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

    public function test_miss_fetches_identity_and_members(): void
    {
        //! @section Arrange
        $httpCalls = [];
        $cache = $this->cacheWithHttp($httpCalls);

        //! @section Act
        $result = $cache->resolve('tok', null);

        //! @section Assert
        $this->assertTrue($result->isSuccess());
        $campaign = $result->getValue();
        $this->assertSame('user-1', $campaign->userId);
        $this->assertSame('camp-1', $campaign->campaignId);
        $this->assertCount(1, $campaign->members);
        $this->assertSame('Epoint Man', $campaign->members[0]->fullName);
        $this->assertCount(2, $httpCalls);
        $this->assertStringContainsString('/identity', $httpCalls[0]['url']);
        $this->assertStringContainsString('/members', $httpCalls[1]['url']);
    }

    public function test_hit_within_ttl_skips_http_when_user_id_known(): void
    {
        //! @section Arrange
        $httpCalls = [];
        $cache = $this->cacheWithHttp($httpCalls);
        $cache->resolve('tok', null);
        $httpCalls = [];
        $cache = $this->cacheWithHttp($httpCalls);

        //! @section Act
        $result = $cache->resolve('tok', 'user-1');

        //! @section Assert
        $this->assertTrue($result->isSuccess());
        $this->assertSame('Epoint Man', $result->getValue()->members[0]->fullName);
        $this->assertSame([], $httpCalls);
    }

    public function test_stale_cache_refetches(): void
    {
        //! @section Arrange
        $httpCalls = [];
        $cache = $this->cacheWithHttp($httpCalls);
        $cache->resolve('tok', null);
        foreach (glob($this->cacheDir . '/*') ?: [] as $file) {
            touch($file, time() - MemberCache::TTL_SECONDS - 1);
        }
        $httpCalls = [];
        $cache = $this->cacheWithHttp($httpCalls);

        //! @section Act
        $result = $cache->resolve('tok', 'user-1');

        //! @section Assert
        $this->assertTrue($result->isSuccess());
        $this->assertNotSame([], $httpCalls);
        $this->assertStringContainsString('/identity', $httpCalls[0]['url']);
        $this->assertStringContainsString('/members', $httpCalls[1]['url']);
    }

    public function test_keys_are_isolated_per_user(): void
    {
        //! @section Arrange
        $user1Written = false;
        $cache = new MemberCache(
            new PatreonMemberService(function (string $method, string $url) use (&$user1Written): string {
                if (str_contains($url, '/identity')) {
                    $userId = $user1Written ? 'user-2' : 'user-1';
                    $campaignId = $user1Written ? 'camp-2' : 'camp-1';
                    return json_encode([
                        'data' => [
                            'id' => $userId,
                            'type' => 'user',
                            'relationships' => [
                                'campaign' => ['data' => ['id' => $campaignId, 'type' => 'campaign']],
                            ],
                        ],
                    ]);
                }
                $name = $user1Written ? 'Other Creator' : 'Epoint Man';
                $user1Written = true;
                return json_encode([
                    'data' => [[
                        'id' => 'mem',
                        'type' => 'member',
                        'attributes' => ['full_name' => $name, 'patron_status' => 'active_patron'],
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
            }),
            FilePath::fromString($this->cacheDir)
        );

        //! @section Act
        $first = $cache->resolve('tok', null);
        $second = $cache->resolve('tok', null);
        $cachedFirst = $cache->resolve('tok', 'user-1');

        //! @section Assert
        $this->assertSame('user-1', $first->getValue()->userId);
        $this->assertSame('Epoint Man', $first->getValue()->members[0]->fullName);
        $this->assertSame('user-2', $second->getValue()->userId);
        $this->assertSame('Other Creator', $second->getValue()->members[0]->fullName);
        $this->assertSame('Epoint Man', $cachedFirst->getValue()->members[0]->fullName);
    }

    //! @param list<array{method: string, url: string}> $httpCalls
    private function cacheWithHttp(array &$httpCalls): MemberCache
    {
        return new MemberCache(
            new PatreonMemberService(function (string $method, string $url) use (&$httpCalls): string {
                $httpCalls[] = compact('method', 'url');
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
            }),
            FilePath::fromString($this->cacheDir)
        );
    }
}
