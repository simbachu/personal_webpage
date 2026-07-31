<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Http;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use App\Shared\Http\HttpStatusCode;

//! @brief Compact contracts for HttpStatusCode (invalid input, round-trip, categories)
class HttpStatusCodeTest extends TestCase
{
    #[DataProvider('provideDefinedStatusCodes')]
    public function test_from_int_round_trips_each_defined_code(int $code, HttpStatusCode $expected): void
    {
        //! @section Act
        $status = HttpStatusCode::fromInt($code);

        //! @section Assert
        $this->assertSame($expected, $status);
        $this->assertTrue(HttpStatusCode::isValid($code));
        $this->assertSame($code, $status->getValue());
        $this->assertStringContainsString((string) $code, $status->getStatusLine());
        $this->assertNotSame('', $status->getDescription());
    }

    //! @return array<string, array{0: int, 1: HttpStatusCode}>
    public static function provideDefinedStatusCodes(): array
    {
        $cases = [];
        foreach (HttpStatusCode::cases() as $case) {
            $cases[$case->name] = [$case->value, $case];
        }
        return $cases;
    }

    public function test_from_int_rejects_invalid_status_code(): void
    {
        //! @section Arrange
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid HTTP status code: 999');

        //! @section Act
        HttpStatusCode::fromInt(999);
    }

    public function test_is_valid_rejects_unknown_codes(): void
    {
        //! @section Assert
        $this->assertFalse(HttpStatusCode::isValid(0));
        $this->assertFalse(HttpStatusCode::isValid(999));
        $this->assertFalse(HttpStatusCode::isValid(418));
    }

    public function test_category_helpers_use_status_ranges(): void
    {
        //! @section Assert
        $this->assertTrue(HttpStatusCode::OK->isSuccess());
        $this->assertFalse(HttpStatusCode::OK->isError());

        $this->assertTrue(HttpStatusCode::MOVED_PERMANENTLY->isRedirection());

        $this->assertTrue(HttpStatusCode::NOT_FOUND->isClientError());
        $this->assertTrue(HttpStatusCode::NOT_FOUND->isError());

        $this->assertTrue(HttpStatusCode::INTERNAL_SERVER_ERROR->isServerError());
        $this->assertTrue(HttpStatusCode::INTERNAL_SERVER_ERROR->isError());
    }
}
