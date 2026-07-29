<?php

declare(strict_types=1);

namespace App\Type;

//! @brief Immutable value object representing a Git commit summary
final class CommitInfo
{
    public function __construct(
        public readonly string $sha,
        public readonly string $date,
        public readonly string $message,
        public readonly string $url
    ) {}
}


