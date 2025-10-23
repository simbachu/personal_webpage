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
        //! @section Act - 2xx Success
        $okValue = HttpStatusCode::OK->value;
        $createdValue = HttpStatusCode::CREATED->value;
        $acceptedValue = HttpStatusCode::ACCEPTED->value;
        $noContentValue = HttpStatusCode::NO_CONTENT->value;

        //! @section Assert - 2xx Success
        $this->assertSame(200, $okValue);
        $this->assertSame(201, $createdValue);
        $this->assertSame(202, $acceptedValue);
        $this->assertSame(204, $noContentValue);

        //! @section Act - 3xx Redirection
        $movedPermanentlyValue = HttpStatusCode::MOVED_PERMANENTLY->value;
        $foundValue = HttpStatusCode::FOUND->value;
        $seeOtherValue = HttpStatusCode::SEE_OTHER->value;
        $notModifiedValue = HttpStatusCode::NOT_MODIFIED->value;

        //! @section Assert - 3xx Redirection
        $this->assertSame(301, $movedPermanentlyValue);
        $this->assertSame(302, $foundValue);
        $this->assertSame(303, $seeOtherValue);
        $this->assertSame(304, $notModifiedValue);

        //! @section Act - 4xx Client Error
        $badRequestValue = HttpStatusCode::BAD_REQUEST->value;
        $unauthorizedValue = HttpStatusCode::UNAUTHORIZED->value;
        $forbiddenValue = HttpStatusCode::FORBIDDEN->value;
        $notFoundValue = HttpStatusCode::NOT_FOUND->value;
        $methodNotAllowedValue = HttpStatusCode::METHOD_NOT_ALLOWED->value;
        $conflictValue = HttpStatusCode::CONFLICT->value;
        $unprocessableEntityValue = HttpStatusCode::UNPROCESSABLE_ENTITY->value;

        //! @section Assert - 4xx Client Error
        $this->assertSame(400, $badRequestValue);
        $this->assertSame(401, $unauthorizedValue);
        $this->assertSame(403, $forbiddenValue);
        $this->assertSame(404, $notFoundValue);
        $this->assertSame(405, $methodNotAllowedValue);
        $this->assertSame(409, $conflictValue);
        $this->assertSame(422, $unprocessableEntityValue);

        //! @section Act - 5xx Server Error
        $internalServerErrorValue = HttpStatusCode::INTERNAL_SERVER_ERROR->value;
        $notImplementedValue = HttpStatusCode::NOT_IMPLEMENTED->value;
        $badGatewayValue = HttpStatusCode::BAD_GATEWAY->value;
        $serviceUnavailableValue = HttpStatusCode::SERVICE_UNAVAILABLE->value;

        //! @section Assert - 5xx Server Error
        $this->assertSame(500, $internalServerErrorValue);
        $this->assertSame(501, $notImplementedValue);
        $this->assertSame(502, $badGatewayValue);
        $this->assertSame(503, $serviceUnavailableValue);
    }

    public function test_from_int_with_valid_status_codes(): void
    {
        //! @section Act - 2xx Success
        $okFromInt = HttpStatusCode::fromInt(200);
        $createdFromInt = HttpStatusCode::fromInt(201);
        $acceptedFromInt = HttpStatusCode::fromInt(202);
        $noContentFromInt = HttpStatusCode::fromInt(204);

        //! @section Assert - 2xx Success
        $this->assertSame(HttpStatusCode::OK, $okFromInt);
        $this->assertSame(HttpStatusCode::CREATED, $createdFromInt);
        $this->assertSame(HttpStatusCode::ACCEPTED, $acceptedFromInt);
        $this->assertSame(HttpStatusCode::NO_CONTENT, $noContentFromInt);

        //! @section Act - 3xx Redirection
        $movedPermanentlyFromInt = HttpStatusCode::fromInt(301);
        $foundFromInt = HttpStatusCode::fromInt(302);
        $seeOtherFromInt = HttpStatusCode::fromInt(303);
        $notModifiedFromInt = HttpStatusCode::fromInt(304);

        //! @section Assert - 3xx Redirection
        $this->assertSame(HttpStatusCode::MOVED_PERMANENTLY, $movedPermanentlyFromInt);
        $this->assertSame(HttpStatusCode::FOUND, $foundFromInt);
        $this->assertSame(HttpStatusCode::SEE_OTHER, $seeOtherFromInt);
        $this->assertSame(HttpStatusCode::NOT_MODIFIED, $notModifiedFromInt);

        //! @section Act - 4xx Client Error
        $badRequestFromInt = HttpStatusCode::fromInt(400);
        $unauthorizedFromInt = HttpStatusCode::fromInt(401);
        $forbiddenFromInt = HttpStatusCode::fromInt(403);
        $notFoundFromInt = HttpStatusCode::fromInt(404);
        $methodNotAllowedFromInt = HttpStatusCode::fromInt(405);
        $conflictFromInt = HttpStatusCode::fromInt(409);
        $unprocessableEntityFromInt = HttpStatusCode::fromInt(422);

        //! @section Assert - 4xx Client Error
        $this->assertSame(HttpStatusCode::BAD_REQUEST, $badRequestFromInt);
        $this->assertSame(HttpStatusCode::UNAUTHORIZED, $unauthorizedFromInt);
        $this->assertSame(HttpStatusCode::FORBIDDEN, $forbiddenFromInt);
        $this->assertSame(HttpStatusCode::NOT_FOUND, $notFoundFromInt);
        $this->assertSame(HttpStatusCode::METHOD_NOT_ALLOWED, $methodNotAllowedFromInt);
        $this->assertSame(HttpStatusCode::CONFLICT, $conflictFromInt);
        $this->assertSame(HttpStatusCode::UNPROCESSABLE_ENTITY, $unprocessableEntityFromInt);

        //! @section Act - 5xx Server Error
        $internalServerErrorFromInt = HttpStatusCode::fromInt(500);
        $notImplementedFromInt = HttpStatusCode::fromInt(501);
        $badGatewayFromInt = HttpStatusCode::fromInt(502);
        $serviceUnavailableFromInt = HttpStatusCode::fromInt(503);

        //! @section Assert - 5xx Server Error
        $this->assertSame(HttpStatusCode::INTERNAL_SERVER_ERROR, $internalServerErrorFromInt);
        $this->assertSame(HttpStatusCode::NOT_IMPLEMENTED, $notImplementedFromInt);
        $this->assertSame(HttpStatusCode::BAD_GATEWAY, $badGatewayFromInt);
        $this->assertSame(HttpStatusCode::SERVICE_UNAVAILABLE, $serviceUnavailableFromInt);
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
        //! @section Act
        $isValid200 = HttpStatusCode::isValid(200);
        $isValid201 = HttpStatusCode::isValid(201);
        $isValid202 = HttpStatusCode::isValid(202);
        $isValid204 = HttpStatusCode::isValid(204);
        $isValid301 = HttpStatusCode::isValid(301);
        $isValid302 = HttpStatusCode::isValid(302);
        $isValid303 = HttpStatusCode::isValid(303);
        $isValid304 = HttpStatusCode::isValid(304);
        $isValid400 = HttpStatusCode::isValid(400);
        $isValid401 = HttpStatusCode::isValid(401);
        $isValid403 = HttpStatusCode::isValid(403);
        $isValid404 = HttpStatusCode::isValid(404);
        $isValid405 = HttpStatusCode::isValid(405);
        $isValid409 = HttpStatusCode::isValid(409);
        $isValid422 = HttpStatusCode::isValid(422);
        $isValid500 = HttpStatusCode::isValid(500);
        $isValid501 = HttpStatusCode::isValid(501);
        $isValid502 = HttpStatusCode::isValid(502);
        $isValid503 = HttpStatusCode::isValid(503);

        //! @section Assert
        $this->assertTrue($isValid200);
        $this->assertTrue($isValid201);
        $this->assertTrue($isValid202);
        $this->assertTrue($isValid204);
        $this->assertTrue($isValid301);
        $this->assertTrue($isValid302);
        $this->assertTrue($isValid303);
        $this->assertTrue($isValid304);
        $this->assertTrue($isValid400);
        $this->assertTrue($isValid401);
        $this->assertTrue($isValid403);
        $this->assertTrue($isValid404);
        $this->assertTrue($isValid405);
        $this->assertTrue($isValid409);
        $this->assertTrue($isValid422);
        $this->assertTrue($isValid500);
        $this->assertTrue($isValid501);
        $this->assertTrue($isValid502);
        $this->assertTrue($isValid503);
    }

    public function test_is_valid_with_invalid_status_codes(): void
    {
        //! @section Act
        $isValid999 = HttpStatusCode::isValid(999);
        $isValid418 = HttpStatusCode::isValid(418);
        $isValidNegative1 = HttpStatusCode::isValid(-1);
        $isValid0 = HttpStatusCode::isValid(0);
        $isValid199 = HttpStatusCode::isValid(199);
        $isValid600 = HttpStatusCode::isValid(600);

        //! @section Assert
        $this->assertFalse($isValid999);
        $this->assertFalse($isValid418);
        $this->assertFalse($isValidNegative1);
        $this->assertFalse($isValid0);
        $this->assertFalse($isValid199);
        $this->assertFalse($isValid600);
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
        //! @section Act - 2xx Success
        $okDescription = HttpStatusCode::OK->getDescription();
        $createdDescription = HttpStatusCode::CREATED->getDescription();
        $acceptedDescription = HttpStatusCode::ACCEPTED->getDescription();
        $noContentDescription = HttpStatusCode::NO_CONTENT->getDescription();

        //! @section Assert - 2xx Success
        $this->assertSame('OK', $okDescription);
        $this->assertSame('Created', $createdDescription);
        $this->assertSame('Accepted', $acceptedDescription);
        $this->assertSame('No Content', $noContentDescription);

        //! @section Act - 3xx Redirection
        $movedPermanentlyDescription = HttpStatusCode::MOVED_PERMANENTLY->getDescription();
        $foundDescription = HttpStatusCode::FOUND->getDescription();
        $seeOtherDescription = HttpStatusCode::SEE_OTHER->getDescription();
        $notModifiedDescription = HttpStatusCode::NOT_MODIFIED->getDescription();

        //! @section Assert - 3xx Redirection
        $this->assertSame('Moved Permanently', $movedPermanentlyDescription);
        $this->assertSame('Found', $foundDescription);
        $this->assertSame('See Other', $seeOtherDescription);
        $this->assertSame('Not Modified', $notModifiedDescription);

        //! @section Act - 4xx Client Error
        $badRequestDescription = HttpStatusCode::BAD_REQUEST->getDescription();
        $unauthorizedDescription = HttpStatusCode::UNAUTHORIZED->getDescription();
        $forbiddenDescription = HttpStatusCode::FORBIDDEN->getDescription();
        $notFoundDescription = HttpStatusCode::NOT_FOUND->getDescription();
        $methodNotAllowedDescription = HttpStatusCode::METHOD_NOT_ALLOWED->getDescription();
        $conflictDescription = HttpStatusCode::CONFLICT->getDescription();
        $unprocessableEntityDescription = HttpStatusCode::UNPROCESSABLE_ENTITY->getDescription();

        //! @section Assert - 4xx Client Error
        $this->assertSame('Bad Request', $badRequestDescription);
        $this->assertSame('Unauthorized', $unauthorizedDescription);
        $this->assertSame('Forbidden', $forbiddenDescription);
        $this->assertSame('Not Found', $notFoundDescription);
        $this->assertSame('Method Not Allowed', $methodNotAllowedDescription);
        $this->assertSame('Conflict', $conflictDescription);
        $this->assertSame('Unprocessable Entity', $unprocessableEntityDescription);

        //! @section Act - 5xx Server Error
        $internalServerErrorDescription = HttpStatusCode::INTERNAL_SERVER_ERROR->getDescription();
        $notImplementedDescription = HttpStatusCode::NOT_IMPLEMENTED->getDescription();
        $badGatewayDescription = HttpStatusCode::BAD_GATEWAY->getDescription();
        $serviceUnavailableDescription = HttpStatusCode::SERVICE_UNAVAILABLE->getDescription();

        //! @section Assert - 5xx Server Error
        $this->assertSame('Internal Server Error', $internalServerErrorDescription);
        $this->assertSame('Not Implemented', $notImplementedDescription);
        $this->assertSame('Bad Gateway', $badGatewayDescription);
        $this->assertSame('Service Unavailable', $serviceUnavailableDescription);
    }

    public function test_is_success(): void
    {
        //! @section Act - 2xx Success
        $okIsSuccess = HttpStatusCode::OK->isSuccess();
        $createdIsSuccess = HttpStatusCode::CREATED->isSuccess();
        $acceptedIsSuccess = HttpStatusCode::ACCEPTED->isSuccess();
        $noContentIsSuccess = HttpStatusCode::NO_CONTENT->isSuccess();

        //! @section Assert - 2xx Success
        $this->assertTrue($okIsSuccess);
        $this->assertTrue($createdIsSuccess);
        $this->assertTrue($acceptedIsSuccess);
        $this->assertTrue($noContentIsSuccess);

        //! @section Act - Non-success codes
        $movedPermanentlyIsSuccess = HttpStatusCode::MOVED_PERMANENTLY->isSuccess();
        $badRequestIsSuccess = HttpStatusCode::BAD_REQUEST->isSuccess();
        $notFoundIsSuccess = HttpStatusCode::NOT_FOUND->isSuccess();
        $internalServerErrorIsSuccess = HttpStatusCode::INTERNAL_SERVER_ERROR->isSuccess();

        //! @section Assert - Non-success codes
        $this->assertFalse($movedPermanentlyIsSuccess);
        $this->assertFalse($badRequestIsSuccess);
        $this->assertFalse($notFoundIsSuccess);
        $this->assertFalse($internalServerErrorIsSuccess);
    }

    public function test_is_redirection(): void
    {
        //! @section Act - 3xx Redirection
        $movedPermanentlyIsRedirection = HttpStatusCode::MOVED_PERMANENTLY->isRedirection();
        $foundIsRedirection = HttpStatusCode::FOUND->isRedirection();
        $seeOtherIsRedirection = HttpStatusCode::SEE_OTHER->isRedirection();
        $notModifiedIsRedirection = HttpStatusCode::NOT_MODIFIED->isRedirection();

        //! @section Assert - 3xx Redirection
        $this->assertTrue($movedPermanentlyIsRedirection);
        $this->assertTrue($foundIsRedirection);
        $this->assertTrue($seeOtherIsRedirection);
        $this->assertTrue($notModifiedIsRedirection);

        //! @section Act - Non-redirection codes
        $okIsRedirection = HttpStatusCode::OK->isRedirection();
        $badRequestIsRedirection = HttpStatusCode::BAD_REQUEST->isRedirection();
        $notFoundIsRedirection = HttpStatusCode::NOT_FOUND->isRedirection();
        $internalServerErrorIsRedirection = HttpStatusCode::INTERNAL_SERVER_ERROR->isRedirection();

        //! @section Assert - Non-redirection codes
        $this->assertFalse($okIsRedirection);
        $this->assertFalse($badRequestIsRedirection);
        $this->assertFalse($notFoundIsRedirection);
        $this->assertFalse($internalServerErrorIsRedirection);
    }

    public function test_is_client_error(): void
    {
        //! @section Act - 4xx Client Error
        $badRequestIsClientError = HttpStatusCode::BAD_REQUEST->isClientError();
        $unauthorizedIsClientError = HttpStatusCode::UNAUTHORIZED->isClientError();
        $forbiddenIsClientError = HttpStatusCode::FORBIDDEN->isClientError();
        $notFoundIsClientError = HttpStatusCode::NOT_FOUND->isClientError();
        $methodNotAllowedIsClientError = HttpStatusCode::METHOD_NOT_ALLOWED->isClientError();
        $conflictIsClientError = HttpStatusCode::CONFLICT->isClientError();
        $unprocessableEntityIsClientError = HttpStatusCode::UNPROCESSABLE_ENTITY->isClientError();

        //! @section Assert - 4xx Client Error
        $this->assertTrue($badRequestIsClientError);
        $this->assertTrue($unauthorizedIsClientError);
        $this->assertTrue($forbiddenIsClientError);
        $this->assertTrue($notFoundIsClientError);
        $this->assertTrue($methodNotAllowedIsClientError);
        $this->assertTrue($conflictIsClientError);
        $this->assertTrue($unprocessableEntityIsClientError);

        //! @section Act - Non-client error codes
        $okIsClientError = HttpStatusCode::OK->isClientError();
        $movedPermanentlyIsClientError = HttpStatusCode::MOVED_PERMANENTLY->isClientError();
        $internalServerErrorIsClientError = HttpStatusCode::INTERNAL_SERVER_ERROR->isClientError();

        //! @section Assert - Non-client error codes
        $this->assertFalse($okIsClientError);
        $this->assertFalse($movedPermanentlyIsClientError);
        $this->assertFalse($internalServerErrorIsClientError);
    }

    public function test_is_server_error(): void
    {
        //! @section Act - 5xx Server Error
        $internalServerErrorIsServerError = HttpStatusCode::INTERNAL_SERVER_ERROR->isServerError();
        $notImplementedIsServerError = HttpStatusCode::NOT_IMPLEMENTED->isServerError();
        $badGatewayIsServerError = HttpStatusCode::BAD_GATEWAY->isServerError();
        $serviceUnavailableIsServerError = HttpStatusCode::SERVICE_UNAVAILABLE->isServerError();

        //! @section Assert - 5xx Server Error
        $this->assertTrue($internalServerErrorIsServerError);
        $this->assertTrue($notImplementedIsServerError);
        $this->assertTrue($badGatewayIsServerError);
        $this->assertTrue($serviceUnavailableIsServerError);

        //! @section Act - Non-server error codes
        $okIsServerError = HttpStatusCode::OK->isServerError();
        $movedPermanentlyIsServerError = HttpStatusCode::MOVED_PERMANENTLY->isServerError();
        $badRequestIsServerError = HttpStatusCode::BAD_REQUEST->isServerError();
        $notFoundIsServerError = HttpStatusCode::NOT_FOUND->isServerError();

        //! @section Assert - Non-server error codes
        $this->assertFalse($okIsServerError);
        $this->assertFalse($movedPermanentlyIsServerError);
        $this->assertFalse($badRequestIsServerError);
        $this->assertFalse($notFoundIsServerError);
    }

    public function test_is_error(): void
    {
        //! @section Act - Error codes (4xx and 5xx)
        $badRequestIsError = HttpStatusCode::BAD_REQUEST->isError();
        $notFoundIsError = HttpStatusCode::NOT_FOUND->isError();
        $internalServerErrorIsError = HttpStatusCode::INTERNAL_SERVER_ERROR->isError();
        $serviceUnavailableIsError = HttpStatusCode::SERVICE_UNAVAILABLE->isError();

        //! @section Assert - Error codes (4xx and 5xx)
        $this->assertTrue($badRequestIsError);
        $this->assertTrue($notFoundIsError);
        $this->assertTrue($internalServerErrorIsError);
        $this->assertTrue($serviceUnavailableIsError);

        //! @section Act - Non-error codes
        $okIsError = HttpStatusCode::OK->isError();
        $createdIsError = HttpStatusCode::CREATED->isError();
        $movedPermanentlyIsError = HttpStatusCode::MOVED_PERMANENTLY->isError();
        $foundIsError = HttpStatusCode::FOUND->isError();

        //! @section Assert - Non-error codes
        $this->assertFalse($okIsError);
        $this->assertFalse($createdIsError);
        $this->assertFalse($movedPermanentlyIsError);
        $this->assertFalse($foundIsError);
    }

    public function test_to_string(): void
    {
        //! @section Act
        $okString = HttpStatusCode::OK->toString();
        $notFoundString = HttpStatusCode::NOT_FOUND->toString();
        $internalServerErrorString = HttpStatusCode::INTERNAL_SERVER_ERROR->toString();

        //! @section Assert
        $this->assertSame('200', $okString);
        $this->assertSame('404', $notFoundString);
        $this->assertSame('500', $internalServerErrorString);
    }

    public function test_get_value(): void
    {
        //! @section Act
        $okValue = HttpStatusCode::OK->getValue();
        $notFoundValue = HttpStatusCode::NOT_FOUND->getValue();
        $internalServerErrorValue = HttpStatusCode::INTERNAL_SERVER_ERROR->getValue();

        //! @section Assert
        $this->assertSame(200, $okValue);
        $this->assertSame(404, $notFoundValue);
        $this->assertSame(500, $internalServerErrorValue);
    }

    public function test_get_status_line(): void
    {
        //! @section Act
        $okStatusLine = HttpStatusCode::OK->getStatusLine();
        $notFoundStatusLine = HttpStatusCode::NOT_FOUND->getStatusLine();
        $internalServerErrorStatusLine = HttpStatusCode::INTERNAL_SERVER_ERROR->getStatusLine();
        $createdStatusLine = HttpStatusCode::CREATED->getStatusLine();
        $movedPermanentlyStatusLine = HttpStatusCode::MOVED_PERMANENTLY->getStatusLine();

        //! @section Assert
        $this->assertSame('200 OK', $okStatusLine);
        $this->assertSame('404 Not Found', $notFoundStatusLine);
        $this->assertSame('500 Internal Server Error', $internalServerErrorStatusLine);
        $this->assertSame('201 Created', $createdStatusLine);
        $this->assertSame('301 Moved Permanently', $movedPermanentlyStatusLine);
    }

    public function test_enum_comparison(): void
    {
        //! @section Arrange
        $status1 = HttpStatusCode::OK;
        $status2 = HttpStatusCode::OK;
        $status3 = HttpStatusCode::NOT_FOUND;

        //! @section Act
        $status1EqualsStatus2 = $status1 === $status2;
        $status1EqualsStatus3 = $status1 === $status3;

        //! @section Assert
        $this->assertSame($status1, $status2);
        $this->assertNotSame($status1, $status3);
        $this->assertTrue($status1EqualsStatus2);
        $this->assertFalse($status1EqualsStatus3);
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

        //! @section Act
        $okHasKey = array_key_exists(HttpStatusCode::OK->value, $statusMap);
        $notFoundHasKey = array_key_exists(HttpStatusCode::NOT_FOUND->value, $statusMap);
        $internalServerErrorHasKey = array_key_exists(HttpStatusCode::INTERNAL_SERVER_ERROR->value, $statusMap);

        $okValue = $statusMap[HttpStatusCode::OK->value];
        $notFoundValue = $statusMap[HttpStatusCode::NOT_FOUND->value];
        $internalServerErrorValue = $statusMap[HttpStatusCode::INTERNAL_SERVER_ERROR->value];

        //! @section Assert
        $this->assertTrue($okHasKey);
        $this->assertTrue($notFoundHasKey);
        $this->assertTrue($internalServerErrorHasKey);

        $this->assertSame('Success', $okValue);
        $this->assertSame('Not Found', $notFoundValue);
        $this->assertSame('Server Error', $internalServerErrorValue);
    }

    public function test_status_code_categories_are_mutually_exclusive(): void
    {
        //! @section Act - Each status code should only be in one category
        $allStatusCodes = HttpStatusCode::cases();
        $categoryCounts = [];

        foreach ($allStatusCodes as $status) {
            $categories = [
                $status->isSuccess(),
                $status->isRedirection(),
                $status->isClientError(),
                $status->isServerError()
            ];

            $categoryCounts[$status->value] = array_sum($categories);
        }

        //! @section Assert - Each status code should only be in one category
        foreach ($allStatusCodes as $status) {
            $this->assertEquals(1, $categoryCounts[$status->value],
                "Status code {$status->value} should be in exactly one category");
        }
    }

    public function test_error_detection_consistency(): void
    {
        //! @section Act - isError() should be true for both client and server errors
        $allStatusCodes = HttpStatusCode::cases();
        $consistencyResults = [];

        foreach ($allStatusCodes as $status) {
            $expectedIsError = $status->isClientError() || $status->isServerError();
            $actualIsError = $status->isError();
            $consistencyResults[$status->value] = [
                'expected' => $expectedIsError,
                'actual' => $actualIsError,
                'isConsistent' => $expectedIsError === $actualIsError
            ];
        }

        //! @section Assert - isError() should be true for both client and server errors
        foreach ($allStatusCodes as $status) {
            $result = $consistencyResults[$status->value];
            $this->assertTrue($result['isConsistent'],
                "isError() should be consistent with isClientError() and isServerError() for status {$status->value}");
        }
    }

    public function test_status_line_format_consistency(): void
    {
        //! @section Act - Status line should always be "code description"
        $allStatusCodes = HttpStatusCode::cases();
        $formatValidationResults = [];

        foreach ($allStatusCodes as $status) {
            $statusLine = $status->getStatusLine();
            $expectedFormat = $status->value . ' ' . $status->getDescription();

            $formatValidationResults[$status->value] = [
                'actualLine' => $statusLine,
                'expectedFormat' => $expectedFormat,
                'formatMatches' => $expectedFormat === $statusLine,
                'startsWithCode' => str_starts_with($statusLine, (string)$status->value),
                'containsSpace' => str_contains($statusLine, ' '),
                'isNotEmpty' => !empty($statusLine)
            ];
        }

        //! @section Assert - Status line should always be "code description"
        foreach ($allStatusCodes as $status) {
            $result = $formatValidationResults[$status->value];

            $this->assertTrue($result['formatMatches'],
                "Status line format should be 'code description' for status {$status->value}");
            $this->assertTrue($result['startsWithCode'],
                "Status line should start with the numeric code for status {$status->value}");
            $this->assertTrue($result['containsSpace'],
                "Status line should contain a space for status {$status->value}");
            $this->assertTrue($result['isNotEmpty'],
                "Status line should not be empty for status {$status->value}");
        }
    }
}
