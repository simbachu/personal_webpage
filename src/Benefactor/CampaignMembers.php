<?php

declare(strict_types=1);

namespace App\Benefactor;

//! @brief Members belonging to one creator's campaign
final class CampaignMembers
{
    //! @brief Construct a campaign member snapshot
    //! @param userId Patreon user id of the creator
    //! @param campaignId Patreon campaign id
    //! @param members Campaign members
    public function __construct(
        public readonly string $userId,
        public readonly string $campaignId,
        public readonly array $members
    ) {
    }

    //! @brief Serialize for cache storage
    //! @return array{userId: string, campaignId: string, members: list<array>}
    public function toArray(): array
    {
        return [
            'userId' => $this->userId,
            'campaignId' => $this->campaignId,
            'members' => array_map(
                static fn (PatronMember $member): array => $member->toArray(),
                $this->members
            ),
        ];
    }

    //! @brief Rehydrate from cache storage
    //! @param data Cached campaign payload
    //! @return self
    public static function fromArray(array $data): self
    {
        $members = [];
        foreach ($data['members'] ?? [] as $memberData) {
            $members[] = PatronMember::fromArray($memberData);
        }

        return new self(
            (string) $data['userId'],
            (string) $data['campaignId'],
            $members
        );
    }
}
