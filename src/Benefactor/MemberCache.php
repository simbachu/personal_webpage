<?php

declare(strict_types=1);

namespace App\Benefactor;

use App\Shared\Support\FilePath;
use App\Shared\Support\Result;

//! @brief Filesystem cache of campaign members keyed by Patreon user id
final class MemberCache
{
    public const TTL_SECONDS = 1800;

    //! @brief Construct the member cache
    //! @param memberService Patreon member API
    //! @param cacheDir Directory for hashed cache files
    //! @param ttlSeconds Freshness window (defaults to 30 minutes)
    public function __construct(
        private readonly PatreonMemberService $memberService,
        private readonly FilePath $cacheDir,
        private readonly int $ttlSeconds = self::TTL_SECONDS
    ) {
    }

    //! @brief Return cached members when fresh, otherwise fetch from Patreon
    //! @param accessToken OAuth access token
    //! @param knownUserId Patreon user id from session, if already known
    //! @return Result<CampaignMembers>
    public function resolve(string $accessToken, ?string $knownUserId): Result
    {
        if ($knownUserId !== null) {
            $cached = $this->readIfFresh($knownUserId);
            if ($cached !== null) {
                return Result::success($cached);
            }
        }

        $identity = $this->memberService->fetchIdentity($accessToken);
        if ($identity->isFailure()) {
            return $identity;
        }

        $userId = $identity->getValue()['userId'];
        $campaignId = $identity->getValue()['campaignId'];

        $cached = $this->readIfFresh($userId);
        if ($cached !== null) {
            return Result::success($cached);
        }

        $members = $this->memberService->fetchMembers($accessToken, $campaignId);
        if ($members->isFailure()) {
            return $members;
        }

        $campaign = new CampaignMembers($userId, $campaignId, $members->getValue());
        $this->write($userId, $campaign);
        return Result::success($campaign);
    }

    //! @brief Read a fresh cache entry for a user
    //! @param userId Patreon user id
    //! @return CampaignMembers|null
    private function readIfFresh(string $userId): ?CampaignMembers
    {
        $path = $this->cacheFile($userId);
        if (!$path->exists() || !$path->isFile()) {
            return null;
        }
        if ($path->isOlderThan($this->ttlSeconds)) {
            return null;
        }

        try {
            $decoded = json_decode($path->readContents(), true);
        } catch (\Throwable) {
            return null;
        }

        if (!is_array($decoded)) {
            return null;
        }

        return CampaignMembers::fromArray($decoded);
    }

    //! @brief Store a campaign snapshot for a user
    //! @param userId Patreon user id
    //! @param campaign Members snapshot
    private function write(string $userId, CampaignMembers $campaign): void
    {
        $this->cacheFile($userId)->writeContents(
            json_encode($campaign->toArray(), JSON_THROW_ON_ERROR)
        );
    }

    //! @brief Hashed cache file for a user id
    //! @param userId Patreon user id
    //! @return FilePath
    private function cacheFile(string $userId): FilePath
    {
        return $this->cacheDir->join('members_' . md5($userId) . '.json');
    }
}
