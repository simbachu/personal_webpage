<?php

declare(strict_types=1);

namespace App\Type;

//! @brief Immutable value object representing repository commit status for main/dev
final class RepositoryInfo
{
    public function __construct(
        public readonly ?CommitInfo $main,
        public readonly ?CommitInfo $dev,
        public readonly ?int $commitsAhead
    ) {}
}


