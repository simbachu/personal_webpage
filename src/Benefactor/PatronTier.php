<?php

declare(strict_types=1);

namespace App\Benefactor;

//! @brief A Patreon reward tier a member is currently entitled to
final class PatronTier
{
    //! @brief Construct a patron tier
    //! @param title Display title from Patreon (e.g. "Hardy Club")
    //! @param amountCents Monthly pledge amount in cents
    public function __construct(
        public readonly string $title,
        public readonly int $amountCents
    ) {
    }

    //! @brief Serialize for cache storage
    //! @return array{title: string, amountCents: int}
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'amountCents' => $this->amountCents,
        ];
    }

    //! @brief Rehydrate from cache storage
    //! @param data Cached tier payload
    //! @return self
    public static function fromArray(array $data): self
    {
        return new self(
            (string) $data['title'],
            (int) $data['amountCents']
        );
    }
}
