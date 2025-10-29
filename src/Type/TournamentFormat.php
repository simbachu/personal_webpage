<?php

declare(strict_types=1);

namespace App\Type;

//! @brief Simple value holder for tournament configuration
final class TournamentFormat
{
    public function __construct(
        public readonly string $format, // e.g. 'swiss-tournament'
        public readonly ?string $playoff, // 'single-elimination' | 'double-elimination' | null
        public readonly ?int $playoffCutoff, // e.g. 16
        public readonly bool $playoffReset // whether to use GF reset in double elim
    ) {}
}


