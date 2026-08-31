<?php

declare(strict_types=1);

namespace App\Benefactor;

//! @brief Query parameters for the Benefactor OAuth callback
final class BenefactorRequest
{
    //! @brief Construct a Benefactor request
    //! @param code Authorization code from Patreon, if present
    //! @param state CSRF state returned by Patreon, if present
    //! @param error OAuth error code from Patreon, if present
    public function __construct(
        public readonly ?string $code = null,
        public readonly ?string $state = null,
        public readonly ?string $error = null
    ) {
    }
}
