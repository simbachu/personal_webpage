<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Http;

use App\Shared\Http\HttpStatusCode;
use App\Shared\Http\RouteResult;
use App\Shared\Http\TemplateName;
use PHPUnit\Framework\TestCase;

//! @brief RouteResult redirect URL is preserved across withData / withStatusCode
final class RouteResultTest extends TestCase
{
    public function test_redirect_url_defaults_to_null(): void
    {
        //! @section Act
        $result = new RouteResult(TemplateName::HOME);

        //! @section Assert
        $this->assertNull($result->getRedirectUrl());
    }

    public function test_redirect_factory_sets_found_and_location(): void
    {
        //! @section Act
        $result = RouteResult::redirect('https://simbachu.com/benefactor', TemplateName::BENEFACTOR);

        //! @section Assert
        $this->assertSame(HttpStatusCode::FOUND, $result->getStatusCode());
        $this->assertSame('https://simbachu.com/benefactor', $result->getRedirectUrl());
        $this->assertSame(TemplateName::BENEFACTOR, $result->getTemplate());
    }

    public function test_with_data_preserves_redirect_url(): void
    {
        //! @section Arrange
        $original = RouteResult::redirect('https://example.test/go', TemplateName::BENEFACTOR);

        //! @section Act
        $merged = $original->withData(['meta' => ['title' => 'Benefactor']]);

        //! @section Assert
        $this->assertSame('https://example.test/go', $merged->getRedirectUrl());
        $this->assertSame(HttpStatusCode::FOUND, $merged->getStatusCode());
        $this->assertSame('Benefactor', $merged->getData()['meta']['title']);
        $this->assertNull($original->getData()['meta'] ?? null);
    }
}
