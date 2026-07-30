<?php

declare(strict_types=1);

namespace App\Cv;

//! @brief Discriminated experience entry rendered by the experience macro
interface ExperienceEntry
{
    //! @brief Template-facing array representation
    //! @return array<string, mixed>
    public function toArray(): array;
}
