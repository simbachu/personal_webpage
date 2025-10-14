<?php

declare(strict_types=1);

namespace Tests\Unit\Type;

use PHPUnit\Framework\TestCase;
use App\Type\HttpStatusCode;

//! @brief Test suite for the HttpStatusCode enum
class HttpStatusCodeTest extends TestCase
{
    public function test_all_http_status_codes_are_defined(): void
    {
        //! @section Act
        $statusCodes = HttpStatusCode::cases();

        //! @section Assert
        $this->assertCount(19, $statusCodes);

        $expectedCodes = [
            200, 201, 202, 204,  // 2xx Success
            301, 302, 303, 304,  // 3xx Redirection
            400, 401, 403, 404, 405, 409, 422,  // 4xx Client Error
            500, 501, 502, 503   // 5xx Server Error
        ];
        $actualCodes = array_column($statusCodes, 'value');

        foreach ($expectedCodes as $expected) {
            $this->assertContains($expected, $actualCodes, "HTTP status code '{$expected}' should be defined");
        }
    }

    public function test_status_code_values_are_correct(): void
    {
        //! @section Act & Assert - 2xx Success
        $this->assertSame(200, HttpStatusCode::OK->value);
        $this->assertSame(201, HttpStatusCode::CREATED->value);
        $this->assertSame(202, HttpStatusCode::ACCEPTED->value);
        $this->assertSame(204, HttpStatusCode::NO_CONTENT->value);

        //! @section Act & Assert - 3xx Redirection
        $this->assertSame(301, HttpStatusCode::MOVED_PERMANENTLY->value);
        $this->assertSame(302, HttpStatusCode::FOUND->value);
        $this->assertSame(303, HttpStatusCode::SEE_OTHER->value);
        $this->assertSame(304, HttpStatusCode::NOT_MODIFIED->value);

        //! @section Act & Assert - 4xx Client Error
        $this->assertSame(400, HttpStatusCode::BAD_REQUEST->value);
        $this->assertSame(401, HttpStatusCode::UNAUTHORIZED->value);
        $this->assertSame(403, HttpStatusCode::FORBIDDEN->value);
        $this->assertSame(404, HttpStatusCode::NOT_FOUND->value);
        $this->assertSame(405, HttpStatusCode::METHOD_NOT_ALLOWED->value);
        $this->assertSame(409, HttpStatusCode::CONFLICT->value);
        $this->assertSame(422, HttpStatusCode::UNPROCESSABLE_ENTITY->value);

        //! @section Act & Assert - 5xx Server Error
        $this->assertSame(500, HttpStatusCode::INTERNAL_SERVER_ERROR->value);
        $this->assertSame(501, HttpStatusCode::NOT_IMPLEMENTED->value);
        $this->assertSame(502, HttpStatusCode::BAD_GATEWAY->value);
        $this->assertSame(503, HttpStatusCode::SERVICE_UNAVAILABLE->value);
    }

    public function test_from_int_with_valid_status_codes(): void
    {
        //! @section Act & Assert - 2xx Success
        $this->assertSame(HttpStatusCode::OK, HttpStatusCode::fromInt(200));
        $this->assertSame(HttpStatusCode::CREATED, HttpStatusCode::fromInt(201));
        $this->assertSame(HttpStatusCode::ACCEPTED, HttpStatusCode::fromInt(202));
        $this->assertSame(HttpStatusCode::NO_CONTENT, HttpStatusCode::fromInt(204));

        //! @section Act & Assert - 3xx Redirection
        $this->assertSame(HttpStatusCode::MOVED_PERMANENTLY, HttpStatusCode::fromInt(301));
        $this->assertSame(HttpStatusCode::FOUND, HttpStatusCode::fromInt(302));
        $this->assertSame(HttpStatusCode::SEE_OTHER, HttpStatusCode::fromInt(303));
        $this->assertSame(HttpStatusCode::NOT_MODIFIED, HttpStatusCode::fromInt(304));

        //! @section Act & Assert - 4xx Client Error
        $this->assertSame(HttpStatusCode::BAD_REQUEST, HttpStatusCode::fromInt(400));
        $this->assertSame(HttpStatusCode::UNAUTHORIZED, HttpStatusCode::fromInt(401));
        $this->assertSame(HttpStatusCode::FORBIDDEN, HttpStatusCode::fromInt(403));
        $this->assertSame(HttpStatusCode::NOT_FOUND, HttpStatusCode::fromInt(404));
        $this->assertSame(HttpStatusCode::METHOD_NOT_ALLOWED, HttpStatusCode::fromInt(405));
        $this->assertSame(HttpStatusCode::CONFLICT, HttpStatusCode::fromInt(409));
        $this->assertSame(HttpStatusCode::UNPROCESSABLE_ENTITY, HttpStatusCode::fromInt(422));

        //! @section Act & Assert - 5xx Server Error
        $this->assertSame(HttpStatusCode::INTERNAL_SERVER_ERROR, HttpStatusCode::fromInt(500));
        $this->assertSame(HttpStatusCode::NOT_IMPLEMENTED, HttpStatusCode::fromInt(501));
        $this->assertSame(HttpStatusCode::BAD_GATEWAY, HttpStatusCode::fromInt(502));
        $this->assertSame(HttpStatusCode::SERVICE_UNAVAILABLE, HttpStatusCode::fromInt(503));
    }

    public function test_from_int_with_invalid_status_code(): void
    {
        //! @section Arrange
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid HTTP status code: 999. Valid codes are: 200, 201, 202, 204, 301, 302, 303, 304, 400, 401, 403, 404, 405, 409, 422, 500, 501, 502, 503');

        //! @section Act
        HttpStatusCode::fromInt(999);
    }

    public function test_from_int_with_unsupported_valid_status_code(): void
    {
        //! @section Arrange
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid HTTP status code: 418. Valid codes are:');

        //! @section Act
        HttpStatusCode::fromInt(418); // I'm a teapot - valid but not in our enum
    }

    public function test_from_int_with_negative_status_code(): void
    {
        //! @section Arrange
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid HTTP status code: -1. Valid codes are:');

        //! @section Act
        HttpStatusCode::fromInt(-1);
    }

    public function test_is_valid_with_valid_status_codes(): void
    {
        //! @section Act & Assert
        $this->assertTrue(HttpStatusCode::isValid(200));
        $this->assertTrue(HttpStatusCode::isValid(201));
        $this->assertTrue(HttpStatusCode::isValid(202));
        $this->assertTrue(HttpStatusCode::isValid(204));
        $this->assertTrue(HttpStatusCode::isValid(301));
        $this->assertTrue(HttpStatusCode::isValid(302));
        $this->assertTrue(HttpStatusCode::isValid(303));
        $this->assertTrue(HttpStatusCode::isValid(304));
        $this->assertTrue(HttpStatusCode::isValid(400));
        $this->assertTrue(HttpStatusCode::isValid(401));
        $this->assertTrue(HttpStatusCode::isValid(403));
        $this->assertTrue(HttpStatusCode::isValid(404));
        $this->assertTrue(HttpStatusCode::isValid(405));
        $this->assertTrue(HttpStatusCode::isValid(409));
        $this->assertTrue(HttpStatusCode::isValid(422));
        $this->assertTrue(HttpStatusCode::isValid(500));
        $this->assertTrue(HttpStatusCode::isValid(501));
        $this->assertTrue(HttpStatusCode::isValid(502));
        $this->assertTrue(HttpStatusCode::isValid(503));
    }

    public function test_is_valid_with_invalid_status_codes(): void
    {
        //! @section Act & Assert
        $this->assertFalse(HttpStatusCode::isValid(999));
        $this->assertFalse(HttpStatusCode::isValid(418));
        $this->assertFalse(HttpStatusCode::isValid(-1));
        $this->assertFalse(HttpStatusCode::isValid(0));
        $this->assertFalse(HttpStatusCode::isValid(199));
        $this->assertFalse(HttpStatusCode::isValid(600));
    }

    public function test_get_all_values(): void
    {
        //! @section Act
        $allValues = HttpStatusCode::getAllValues();

        //! @section Assert
        $this->assertIsArray($allValues);
        $this->assertCount(19, $allValues);

        $expectedValues = [
            200, 201, 202, 204, 301, 302, 303, 304,
            400, 401, 403, 404, 405, 409, 422,
            500, 501, 502, 503
        ];

        foreach ($expectedValues as $expected) {
            $this->assertContains($expected, $allValues);
        }

        $this->assertEquals($expectedValues, $allValues);
    }

    public function test_get_description(): void
    {
        //! @section Act & Assert - 2xx Success
        $this->assertSame('OK', HttpStatusCode::OK->getDescription());
        $this->assertSame('Created', HttpStatusCode::CREATED->getDescription());
        $this->assertSame('Accepted', HttpStatusCode::ACCEPTED->getDescription());
        $this->assertSame('No Content', HttpStatusCode::NO_CONTENT->getDescription());

        //! @section Act & Assert - 3xx Redirection
        $this->assertSame('Moved Permanently', HttpStatusCode::MOVED_PERMANENTLY->getDescription());
        $this->assertSame('Found', HttpStatusCode::FOUND->getDescription());
        $this->assertSame('See Other', HttpStatusCode::SEE_OTHER->getDescription());
        $this->assertSame('Not Modified', HttpStatusCode::NOT_MODIFIED->getDescription());

        //! @section Act & Assert - 4xx Client Error
        $this->assertSame('Bad Request', HttpStatusCode::BAD_REQUEST->getDescription());
        $this->assertSame('Unauthorized', HttpStatusCode::UNAUTHORIZED->getDescription());
        $this->assertSame('Forbidden', HttpStatusCode::FORBIDDEN->getDescription());
        $this->assertSame('Not Found', HttpStatusCode::NOT_FOUND->getDescription());
        $this->assertSame('Method Not Allowed', HttpStatusCode::METHOD_NOT_ALLOWED->getDescription());
        $this->assertSame('Conflict', HttpStatusCode::CONFLICT->getDescription());
        $this->assertSame('Unprocessable Entity', HttpStatusCode::UNPROCESSABLE_ENTITY->getDescription());

        //! @section Act & Assert - 5xx Server Error
        $this->assertSame('Internal Server Error', HttpStatusCode::INTERNAL_SERVER_ERROR->getDescription());
        $this->assertSame('Not Implemented', HttpStatusCode::NOT_IMPLEMENTED->getDescription());
        $this->assertSame('Bad Gateway', HttpStatusCode::BAD_GATEWAY->getDescription());
        $this->assertSame('Service Unavailable', HttpStatusCode::SERVICE_UNAVAILABLE->getDescription());
    }

    public function test_is_success(): void
    {
        //! @section Act & Assert - 2xx Success
        $this->assertTrue(HttpStatusCode::OK->isSuccess());
        $this->assertTrue(HttpStatusCode::CREATED->isSuccess());
        $this->assertTrue(HttpStatusCode::ACCEPTED->isSuccess());
        $this->assertTrue(HttpStatusCode::NO_CONTENT->isSuccess());

        //! @section Act & Assert - Non-success codes
        $this->assertFalse(HttpStatusCode::MOVED_PERMANENTLY->isSuccess());
        $this->assertFalse(HttpStatusCode::BAD_REQUEST->isSuccess());
        $this->assertFalse(HttpStatusCode::NOT_FOUND->isSuccess());
        $this->assertFalse(HttpStatusCode::INTERNAL_SERVER_ERROR->isSuccess());
    }

    public function test_is_redirection(): void
    {
        //! @section Act & Assert - 3xx Redirection
        $this->assertTrue(HttpStatusCode::MOVED_PERMANENTLY->isRedirection());
        $this->assertTrue(HttpStatusCode::FOUND->isRedirection());
        $this->assertTrue(HttpStatusCode::SEE_OTHER->isRedirection());
        $this->assertTrue(HttpStatusCode::NOT_MODIFIED->isRedirection());

        //! @section Act & Assert - Non-redirection codes
        $this->assertFalse(HttpStatusCode::OK->isRedirection());
        $this->assertFalse(HttpStatusCode::BAD_REQUEST->isRedirection());
        $this->assertFalse(HttpStatusCode::NOT_FOUND->isRedirection());
        $this->assertFalse(HttpStatusCode::INTERNAL_SERVER_ERROR->isRedirection());
    }

    public function test_is_client_error(): void
    {
        //! @section Act & Assert - 4xx Client Error
        $this->assertTrue(HttpStatusCode::BAD_REQUEST->isClientError());
        $this->assertTrue(HttpStatusCode::UNAUTHORIZED->isClientError());
        $this->assertTrue(HttpStatusCode::FORBIDDEN->isClientError());
        $this->assertTrue(HttpStatusCode::NOT_FOUND->isClientError());
        $this->assertTrue(HttpStatusCode::METHOD_NOT_ALLOWED->isClientError());
        $this->assertTrue(HttpStatusCode::CONFLICT->isClientError());
        $this->assertTrue(HttpStatusCode::UNPROCESSABLE_ENTITY->isClientError());

        //! @section Act & Assert - Non-client error codes
        $this->assertFalse(HttpStatusCode::OK->isClientError());
        $this->assertFalse(HttpStatusCode::MOVED_PERMANENTLY->isClientError());
        $this->assertFalse(HttpStatusCode::INTERNAL_SERVER_ERROR->isClientError());
    }

    public function test_is_server_error(): void
    {
        //! @section Act & Assert - 5xx Server Error
        $this->assertTrue(HttpStatusCode::INTERNAL_SERVER_ERROR->isServerError());
        $this->assertTrue(HttpStatusCode::NOT_IMPLEMENTED->isServerError());
        $this->assertTrue(HttpStatusCode::BAD_GATEWAY->isServerError());
        $this->assertTrue(HttpStatusCode::SERVICE_UNAVAILABLE->isServerError());

        //! @section Act & Assert - Non-server error codes
        $this->assertFalse(HttpStatusCode::OK->isServerError());
        $this->assertFalse(HttpStatusCode::MOVED_PERMANENTLY->isServerError());
        $this->assertFalse(HttpStatusCode::BAD_REQUEST->isServerError());
        $this->assertFalse(HttpStatusCode::NOT_FOUND->isServerError());
    }

    public function test_is_error(): void
    {
        //! @section Act & Assert - Error codes (4xx and 5xx)
        $this->assertTrue(HttpStatusCode::BAD_REQUEST->isError());
        $this->assertTrue(HttpStatusCode::NOT_FOUND->isError());
        $this->assertTrue(HttpStatusCode::INTERNAL_SERVER_ERROR->isError());
        $this->assertTrue(HttpStatusCode::SERVICE_UNAVAILABLE->isError());

        //! @section Act & Assert - Non-error codes
        $this->assertFalse(HttpStatusCode::OK->isError());
        $this->assertFalse(HttpStatusCode::CREATED->isError());
        $this->assertFalse(HttpStatusCode::MOVED_PERMANENTLY->isError());
        $this->assertFalse(HttpStatusCode::FOUND->isError());
    }

    public function test_to_string(): void
    {
        //! @section Act & Assert
        $this->assertSame('200', HttpStatusCode::OK->toString());
        $this->assertSame('404', HttpStatusCode::NOT_FOUND->toString());
        $this->assertSame('500', HttpStatusCode::INTERNAL_SERVER_ERROR->toString());
    }

    public function test_get_value(): void
    {
        //! @section Act & Assert
        $this->assertSame(200, HttpStatusCode::OK->getValue());
        $this->assertSame(404, HttpStatusCode::NOT_FOUND->getValue());
        $this->assertSame(500, HttpStatusCode::INTERNAL_SERVER_ERROR->getValue());
    }

    public function test_get_status_line(): void
    {
        //! @section Act & Assert
        $this->assertSame('200 OK', HttpStatusCode::OK->getStatusLine());
        $this->assertSame('404 Not Found', HttpStatusCode::NOT_FOUND->getStatusLine());
        $this->assertSame('500 Internal Server Error', HttpStatusCode::INTERNAL_SERVER_ERROR->getStatusLine());
        $this->assertSame('201 Created', HttpStatusCode::CREATED->getStatusLine());
        $this->assertSame('301 Moved Permanently', HttpStatusCode::MOVED_PERMANENTLY->getStatusLine());
    }

    public function test_enum_comparison(): void
    {
        //! @section Arrange
        $status1 = HttpStatusCode::OK;
        $status2 = HttpStatusCode::OK;
        $status3 = HttpStatusCode::NOT_FOUND;

        //! @section Act & Assert
        $this->assertSame($status1, $status2);
        $this->assertNotSame($status1, $status3);
        $this->assertTrue($status1 === $status2);
        $this->assertFalse($status1 === $status3);
    }

    public function test_enum_can_be_used_in_match_statements(): void
    {
        //! @section Arrange
        $status = HttpStatusCode::NOT_FOUND;

        //! @section Act
        $result = match ($status) {
            HttpStatusCode::OK => 'success',
            HttpStatusCode::NOT_FOUND => 'not_found',
            HttpStatusCode::INTERNAL_SERVER_ERROR => 'server_error',
            default => 'other',
        };

        //! @section Assert
        $this->assertSame('not_found', $result);
    }

    public function test_enum_can_be_used_in_switch_statements(): void
    {
        //! @section Arrange
        $status = HttpStatusCode::BAD_REQUEST;

        //! @section Act
        $result = match ($status) {
            HttpStatusCode::OK, HttpStatusCode::CREATED => 'success',
            HttpStatusCode::BAD_REQUEST, HttpStatusCode::NOT_FOUND => 'client_error',
            HttpStatusCode::INTERNAL_SERVER_ERROR => 'server_error',
            default => 'other',
        };

        //! @section Assert
        $this->assertSame('client_error', $result);
    }

    public function test_enum_can_be_serialized(): void
    {
        //! @section Arrange
        $status = HttpStatusCode::NOT_FOUND;

        //! @section Act
        $serialized = serialize($status);
        $unserialized = unserialize($serialized);

        //! @section Assert
        $this->assertSame($status, $unserialized);
        $this->assertSame(404, $unserialized->value);
    }

    public function test_enum_can_be_used_in_array_keys(): void
    {
        //! @section Arrange
        $statusMap = [
            HttpStatusCode::OK->value => 'Success',
            HttpStatusCode::NOT_FOUND->value => 'Not Found',
            HttpStatusCode::INTERNAL_SERVER_ERROR->value => 'Server Error',
        ];

        //! @section Act & Assert
        $this->assertArrayHasKey(HttpStatusCode::OK->value, $statusMap);
        $this->assertArrayHasKey(HttpStatusCode::NOT_FOUND->value, $statusMap);
        $this->assertArrayHasKey(HttpStatusCode::INTERNAL_SERVER_ERROR->value, $statusMap);
        $this->assertSame('Success', $statusMap[HttpStatusCode::OK->value]);
        $this->assertSame('Not Found', $statusMap[HttpStatusCode::NOT_FOUND->value]);
        $this->assertSame('Server Error', $statusMap[HttpStatusCode::INTERNAL_SERVER_ERROR->value]);
    }

    public function test_status_code_categories_are_mutually_exclusive(): void
    {
        //! @section Act & Assert - Each status code should only be in one category
        $allStatusCodes = HttpStatusCode::cases();

        foreach ($allStatusCodes as $status) {
            $categories = [
                $status->isSuccess(),
                $status->isRedirection(),
                $status->isClientError(),
                $status->isServerError()
            ];

            // Exactly one category should be true
            $this->assertEquals(1, array_sum($categories),
                "Status code {$status->value} should be in exactly one category");
        }
    }

    public function test_error_detection_consistency(): void
    {
        //! @section Act & Assert - isError() should be true for both client and server errors
        $allStatusCodes = HttpStatusCode::cases();

        foreach ($allStatusCodes as $status) {
            $expectedIsError = $status->isClientError() || $status->isServerError();
            $this->assertEquals($expectedIsError, $status->isError(),
                "isError() should be consistent with isClientError() and isServerError() for status {$status->value}");
        }
    }

    public function test_status_line_format_consistency(): void
    {
        //! @section Act & Assert - Status line should always be "code description"
        $allStatusCodes = HttpStatusCode::cases();

        foreach ($allStatusCodes as $status) {
            $statusLine = $status->getStatusLine();
            $expectedFormat = $status->value . ' ' . $status->getDescription();

            $this->assertEquals($expectedFormat, $statusLine,
                "Status line format should be 'code description' for status {$status->value}");

            // Should start with the numeric code
            $this->assertStringStartsWith((string)$status->value, $statusLine);

            // Should contain a space
            $this->assertStringContainsString(' ', $statusLine);

            // Should not be empty
            $this->assertNotEmpty($statusLine);
        }
    }
}
