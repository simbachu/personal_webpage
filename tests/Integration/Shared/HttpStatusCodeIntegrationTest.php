<?php

declare(strict_types=1);

namespace Tests\Integration\Shared;

use PHPUnit\Framework\TestCase;
use App\Shared\Http\HttpStatusCode;
use App\Shared\Http\TemplateName;
use App\Shared\Http\RouteResult;

//! @brief RouteResult ↔ HttpStatusCode collaboration (defaults, immutability, chaining)
class HttpStatusCodeIntegrationTest extends TestCase
{
    public function test_route_result_defaults_to_ok_status(): void
    {
        //! @section Act
        $result = new RouteResult(TemplateName::HOME, ['title' => 'Test']);

        //! @section Assert
        $this->assertSame(HttpStatusCode::OK, $result->getStatusCode());
        $this->assertSame(200, $result->getStatusCode()->getValue());
        $this->assertSame('200 OK', $result->getStatusCode()->getStatusLine());
    }

    public function test_with_status_code_creates_new_instance(): void
    {
        //! @section Arrange
        $original = new RouteResult(TemplateName::HOME, ['title' => 'Original'], HttpStatusCode::OK);

        //! @section Act
        $modified = $original->withStatusCode(HttpStatusCode::NOT_FOUND);

        //! @section Assert
        $this->assertNotSame($original, $modified);
        $this->assertSame(HttpStatusCode::OK, $original->getStatusCode());
        $this->assertSame(HttpStatusCode::NOT_FOUND, $modified->getStatusCode());
        $this->assertSame($original->getTemplate(), $modified->getTemplate());
        $this->assertSame($original->getData(), $modified->getData());
    }

    public function test_chaining_status_code_operations(): void
    {
        //! @section Arrange
        $original = new RouteResult(TemplateName::HOME, ['initial' => 'data'], HttpStatusCode::OK);

        //! @section Act
        $result = $original
            ->withData(['additional' => 'data'])
            ->withStatusCode(HttpStatusCode::CREATED);

        //! @section Assert
        $this->assertNotSame($original, $result);
        $this->assertSame(HttpStatusCode::CREATED, $result->getStatusCode());
        $this->assertArrayHasKey('initial', $result->getData());
        $this->assertArrayHasKey('additional', $result->getData());
        $this->assertSame('data', $result->getData()['initial']);
        $this->assertSame('data', $result->getData()['additional']);
    }
}
