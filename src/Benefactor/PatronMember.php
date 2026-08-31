<?php

declare(strict_types=1);

namespace App\Benefactor;

//! @brief A campaign member with currently entitled tiers
final class PatronMember
{
    //! @brief Construct a campaign member
    //! @param fullName Patron display name
    //! @param patronStatus Patreon patron_status (e.g. active_patron)
    //! @param tiers Currently entitled tiers
    public function __construct(
        public readonly string $fullName,
        public readonly string $patronStatus,
        public readonly array $tiers
    ) {
    }

    //! @brief Serialize for cache storage
    //! @return array{fullName: string, patronStatus: string, tiers: list<array{title: string, amountCents: int}>}
    public function toArray(): array
    {
        return [
            'fullName' => $this->fullName,
            'patronStatus' => $this->patronStatus,
            'tiers' => array_map(
                static fn (PatronTier $tier): array => $tier->toArray(),
                $this->tiers
            ),
        ];
    }

    //! @brief Rehydrate from cache storage
    //! @param data Cached member payload
    //! @return self
    public static function fromArray(array $data): self
    {
        $tiers = [];
        foreach ($data['tiers'] ?? [] as $tierData) {
            $tiers[] = PatronTier::fromArray($tierData);
        }

        return new self(
            (string) $data['fullName'],
            (string) $data['patronStatus'],
            $tiers
        );
    }
}
