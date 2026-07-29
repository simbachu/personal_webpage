<?php

declare(strict_types=1);

namespace App\Dex;

//! @brief Cache version for API schema evolution
enum CacheVersion: string
{
    case V1 = 'v1';
    case V2 = 'v2';
}
