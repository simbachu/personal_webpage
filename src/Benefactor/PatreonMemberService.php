<?php

declare(strict_types=1);

namespace App\Benefactor;

use App\Shared\Support\Result;

//! @brief Fetches creator identity and campaign members from Patreon API v2
final class PatreonMemberService
{
    private const IDENTITY_URL = 'https://www.patreon.com/api/oauth2/v2/identity';
    private const MEMBERS_URL = 'https://www.patreon.com/api/oauth2/v2/campaigns/%s/members';

    //! @brief Construct the member service
    //! @param httpClient Callable (method, url, headers, body) returning raw response body
    public function __construct(
        private $httpClient
    ) {
    }

    //! @brief Fetch the authorized creator's user id and campaign id
    //! @param accessToken OAuth access token
    //! @return Result<array{userId: string, campaignId: string}>
    public function fetchIdentity(string $accessToken): Result
    {
        $url = self::IDENTITY_URL . '?' . http_build_query(['include' => 'campaign']);

        $decoded = $this->getJson($accessToken, $url);
        if ($decoded->isFailure()) {
            return $decoded;
        }

        $payload = $decoded->getValue();
        $userId = $payload['data']['id'] ?? null;
        if (!is_string($userId) || $userId === '') {
            return Result::failure('Patreon identity response did not include a user id.');
        }

        $campaignId = $payload['data']['relationships']['campaign']['data']['id'] ?? null;
        if (!is_string($campaignId) || $campaignId === '') {
            return Result::failure('No Patreon campaign is linked to this account.');
        }

        return Result::success([
            'userId' => $userId,
            'campaignId' => $campaignId,
        ]);
    }

    //! @brief Fetch all members for a campaign, following pagination
    //! @param accessToken OAuth access token
    //! @param campaignId Patreon campaign id
    //! @return Result<list<PatronMember>>
    public function fetchMembers(string $accessToken, string $campaignId): Result
    {
        $url = sprintf(self::MEMBERS_URL, rawurlencode($campaignId)) . '?' . http_build_query([
            'include' => 'currently_entitled_tiers',
            'fields' => [
                'member' => 'full_name,patron_status',
                'tier' => 'title,amount_cents',
            ],
        ]);

        $members = [];
        while ($url !== null) {
            $decoded = $this->getJson($accessToken, $url);
            if ($decoded->isFailure()) {
                return $decoded;
            }

            $payload = $decoded->getValue();
            $tiersById = $this->indexTiers($payload['included'] ?? []);
            foreach ($payload['data'] ?? [] as $memberData) {
                if (!is_array($memberData)) {
                    continue;
                }
                $members[] = $this->mapMember($memberData, $tiersById);
            }

            $next = $payload['links']['next'] ?? null;
            $url = is_string($next) && $next !== '' ? $next : null;
        }

        return Result::success($members);
    }

    //! @brief GET JSON with a bearer token
    //! @param accessToken OAuth access token
    //! @param url Absolute request URL
    //! @return Result<array>
    private function getJson(string $accessToken, string $url): Result
    {
        try {
            $raw = ($this->httpClient)(
                'GET',
                $url,
                [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Accept' => 'application/json',
                ],
                null
            );
        } catch (\Throwable $exception) {
            return Result::failure($exception->getMessage());
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return Result::failure('Patreon API response was not valid JSON.');
        }

        if (isset($decoded['errors'])) {
            return Result::failure($this->formatApiErrors($decoded['errors']));
        }

        return Result::success($decoded);
    }

    //! @brief Index included tier resources by id
    //! @param included JSON:API included array
    //! @return array<string, PatronTier>
    private function indexTiers(array $included): array
    {
        $tiers = [];
        foreach ($included as $resource) {
            if (!is_array($resource) || ($resource['type'] ?? '') !== 'tier') {
                continue;
            }
            $id = $resource['id'] ?? null;
            if (!is_string($id) || $id === '') {
                continue;
            }
            $title = (string) ($resource['attributes']['title'] ?? '');
            $amount = (int) ($resource['attributes']['amount_cents'] ?? 0);
            $tiers[$id] = new PatronTier($title, $amount);
        }
        return $tiers;
    }

    //! @brief Map one JSON:API member resource
    //! @param memberData Member resource
    //! @param tiersById Entitled tiers by id
    //! @return PatronMember
    private function mapMember(array $memberData, array $tiersById): PatronMember
    {
        $attributes = is_array($memberData['attributes'] ?? null) ? $memberData['attributes'] : [];
        $tierRefs = $memberData['relationships']['currently_entitled_tiers']['data'] ?? [];
        $tiers = [];
        if (is_array($tierRefs)) {
            foreach ($tierRefs as $tierRef) {
                if (!is_array($tierRef)) {
                    continue;
                }
                $tierId = $tierRef['id'] ?? null;
                if (is_string($tierId) && isset($tiersById[$tierId])) {
                    $tiers[] = $tiersById[$tierId];
                }
            }
        }

        return new PatronMember(
            (string) ($attributes['full_name'] ?? ''),
            (string) ($attributes['patron_status'] ?? ''),
            $tiers
        );
    }

    //! @brief Flatten Patreon JSON:API errors into one message
    //! @param errors Error list
    //! @return string
    private function formatApiErrors(mixed $errors): string
    {
        if (!is_array($errors)) {
            return 'Patreon API request failed.';
        }
        $details = [];
        foreach ($errors as $error) {
            if (is_array($error) && isset($error['detail']) && is_string($error['detail'])) {
                $details[] = $error['detail'];
            }
        }
        return $details === [] ? 'Patreon API request failed.' : implode(' ', $details);
    }
}
