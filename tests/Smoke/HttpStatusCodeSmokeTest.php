<?php

declare(strict_types=1);

namespace Tests\Smoke;

use PHPUnit\Framework\TestCase;
use App\Type\HttpStatusCode;
use App\Type\TemplateName;
use App\Router\RouteResult;

//! @brief Smoke test for HttpStatusCode enum functionality
//!
//! Tests that the HttpStatusCode enum works correctly in real-world scenarios
//! and maintains expected behavior under various conditions.
class HttpStatusCodeSmokeTest extends TestCase
{
    public function test_all_status_codes_can_be_created_and_used(): void
    {
        //! @section Act - Test that all defined status codes work correctly
        $allStatusCodes = HttpStatusCode::cases();
        $validationResults = [];

        foreach ($allStatusCodes as $statusCode) {
            // Create RouteResult with status code
            $result = new RouteResult(TemplateName::HOME, [], $statusCode);

            // Collect validation data
            $validationResults[$statusCode->value] = [
                'statusCodeMatches' => $statusCode === $result->getStatusCode(),
                'valueIsInt' => is_int($statusCode->getValue()),
                'valueInRange' => $statusCode->getValue() >= 100 && $statusCode->getValue() <= 599,
                'descriptionNotEmpty' => !empty($statusCode->getDescription()),
                'descriptionIsString' => is_string($statusCode->getDescription()),
                'statusLineContainsCode' => str_contains($statusCode->getStatusLine(), (string)$statusCode->getValue()),
                'statusLineContainsDescription' => str_contains($statusCode->getStatusLine(), $statusCode->getDescription())
            ];
        }

        //! @section Assert - Test that all defined status codes work correctly
        $this->assertGreaterThan(0, count($allStatusCodes));

        foreach ($allStatusCodes as $statusCode) {
            $result = $validationResults[$statusCode->value];

            // Should be able to create RouteResult with any status code
            $this->assertTrue($result['statusCodeMatches']);

            // Should have a valid integer value
            $this->assertTrue($result['valueIsInt']);
            $this->assertTrue($result['valueInRange']);

            // Should have a non-empty description
            $this->assertTrue($result['descriptionNotEmpty']);
            $this->assertTrue($result['descriptionIsString']);

            // Should have a properly formatted status line
            $this->assertTrue($result['statusLineContainsCode']);
            $this->assertTrue($result['statusLineContainsDescription']);
        }
    }

    public function test_all_status_codes_are_categorized(): void
    {
        //! @section Act
        $allStatusCodes = HttpStatusCode::cases();
        $categories = [
            'success' => [],
            'redirection' => [],
            'client_error' => [],
            'server_error' => []
        ];

        foreach ($allStatusCodes as $statusCode) {
            if ($statusCode->isSuccess()) {
                $categories['success'][] = $statusCode;
            } elseif ($statusCode->isRedirection()) {
                $categories['redirection'][] = $statusCode;
            } elseif ($statusCode->isClientError()) {
                $categories['client_error'][] = $statusCode;
            } elseif ($statusCode->isServerError()) {
                $categories['server_error'][] = $statusCode;
            }
        }

        $totalCategorized = count($categories['success']) +
                          count($categories['redirection']) +
                          count($categories['client_error']) +
                          count($categories['server_error']);

        //! @section Assert
        $this->assertEquals(count($allStatusCodes), $totalCategorized,
            'Every status code should be categorized');
        $this->assertGreaterThan(0, count($categories['success']), 'Should have success status codes');
        $this->assertGreaterThan(0, count($categories['redirection']), 'Should have redirection status codes');
        $this->assertGreaterThan(0, count($categories['client_error']), 'Should have client error status codes');
        $this->assertGreaterThan(0, count($categories['server_error']), 'Should have server error status codes');
    }

    public function test_error_detection_is_consistent(): void
    {
        //! @section Act
        $allStatusCodes = HttpStatusCode::cases();
        $consistencyResults = [];

        foreach ($allStatusCodes as $statusCode) {
            $expectedIsError = $statusCode->isClientError() || $statusCode->isServerError();
            $actualIsError = $statusCode->isError();
            $consistencyResults[$statusCode->value] = $expectedIsError === $actualIsError;
        }

        //! @section Assert
        foreach ($allStatusCodes as $statusCode) {
            $this->assertTrue($consistencyResults[$statusCode->value],
                "isError() should be consistent for status {$statusCode->value}");
        }
    }

    public function test_valid_status_codes_pass_validation(): void
    {
        //! @section Act & Assert
        $validCodes = [200, 201, 404, 500, 301, 400, 422];
        foreach ($validCodes as $code) {
            $this->assertTrue(HttpStatusCode::isValid($code), "Status code {$code} should be valid");

            $statusCode = HttpStatusCode::fromInt($code);
            $this->assertSame($code, $statusCode->getValue());
        }
    }

    public function test_invalid_status_codes_fail_validation(): void
    {
        //! @section Act & Assert
        $invalidCodes = [999, 418, -1, 0, 199, 600, 700];
        foreach ($invalidCodes as $code) {
            $this->assertFalse(HttpStatusCode::isValid($code), "Status code {$code} should be invalid");

            $this->expectException(\InvalidArgumentException::class);
            HttpStatusCode::fromInt($code);
        }
    }

    public function test_enum_works_in_real_world_http_scenarios(): void
    {
        //! @section Arrange - Simulate common HTTP scenarios
        $scenarios = [
            'successful_request' => HttpStatusCode::OK,
            'created_resource' => HttpStatusCode::CREATED,
            'redirect_permanent' => HttpStatusCode::MOVED_PERMANENTLY,
            'redirect_temporary' => HttpStatusCode::FOUND,
            'bad_request' => HttpStatusCode::BAD_REQUEST,
            'unauthorized' => HttpStatusCode::UNAUTHORIZED,
            'forbidden' => HttpStatusCode::FORBIDDEN,
            'not_found' => HttpStatusCode::NOT_FOUND,
            'method_not_allowed' => HttpStatusCode::METHOD_NOT_ALLOWED,
            'conflict' => HttpStatusCode::CONFLICT,
            'validation_error' => HttpStatusCode::UNPROCESSABLE_ENTITY,
            'server_error' => HttpStatusCode::INTERNAL_SERVER_ERROR,
            'not_implemented' => HttpStatusCode::NOT_IMPLEMENTED,
            'bad_gateway' => HttpStatusCode::BAD_GATEWAY,
            'service_unavailable' => HttpStatusCode::SERVICE_UNAVAILABLE,
        ];

        //! @section Act - Each scenario should work correctly
        $validationResults = [];
        foreach ($scenarios as $scenario => $expectedStatusCode) {
            $result = new RouteResult(TemplateName::HOME, ['scenario' => $scenario], $expectedStatusCode);
            $statusCode = $result->getStatusCode();
            $httpCode = $statusCode->getValue();

            $validationResults[$scenario] = [
                'statusCodeMatches' => $expectedStatusCode === $result->getStatusCode(),
                'isInValidCategory' => $statusCode->isSuccess() ||
                                    $statusCode->isRedirection() ||
                                    $statusCode->isClientError() ||
                                    $statusCode->isServerError(),
                'isValidHttpCode' => is_int($httpCode) &&
                                   $httpCode >= 100 &&
                                   $httpCode <= 599
            ];
        }

        //! @section Assert - Each scenario should work correctly
        foreach ($scenarios as $scenario => $expectedStatusCode) {
            $result = $validationResults[$scenario];

            $this->assertTrue($result['statusCodeMatches'],
                "Status code should match for scenario '{$scenario}'");

            $this->assertTrue($result['isInValidCategory'],
                "Status code for scenario '{$scenario}' should be in a valid category");

            $this->assertTrue($result['isValidHttpCode'],
                "HTTP code for scenario '{$scenario}' should be valid");
        }
    }

    public function test_status_code_works_with_route_result_operations(): void
    {
        //! @section Arrange
        $original = new RouteResult(TemplateName::HOME, ['test' => 'data'], HttpStatusCode::OK);

        //! @section Act - Test various operations
        $operations = [
            'with_data' => function($result) {
                return $result->withData(['additional' => 'info']);
            },
            'with_status_ok' => function($result) {
                return $result->withStatusCode(HttpStatusCode::OK);
            },
            'with_status_created' => function($result) {
                return $result->withStatusCode(HttpStatusCode::CREATED);
            },
            'with_status_not_found' => function($result) {
                return $result->withStatusCode(HttpStatusCode::NOT_FOUND);
            },
            'with_status_server_error' => function($result) {
                return $result->withStatusCode(HttpStatusCode::INTERNAL_SERVER_ERROR);
            },
        ];

        $operationResults = [];
        foreach ($operations as $operation => $operationFn) {
            $result = $operationFn($original);
            $statusCode = $result->getStatusCode();

            $operationResults[$operation] = [
                'result' => $result,
                'statusCode' => $statusCode,
                'isNewInstance' => $original !== $result,
                'templatePreserved' => $original->getTemplate() === $result->getTemplate(),
                'statusCodeIsValid' => $statusCode instanceof HttpStatusCode &&
                                     is_int($statusCode->getValue()) &&
                                     !empty($statusCode->getDescription())
            ];
        }

        //! @section Assert - Test various operations
        foreach ($operations as $operation => $operationResult) {
            $result = $operationResults[$operation];

            $this->assertTrue($result['isNewInstance'], "Operation '{$operation}' should return new instance");
            $this->assertTrue($result['templatePreserved'], "Operation '{$operation}' should preserve template");
            $this->assertTrue($result['statusCodeIsValid'], "Operation '{$operation}' should have valid status code");
        }
    }

    public function test_status_code_serialization_and_deserialization(): void
    {
        //! @section Arrange
        $testStatusCodes = [
            HttpStatusCode::OK,
            HttpStatusCode::NOT_FOUND,
            HttpStatusCode::INTERNAL_SERVER_ERROR,
            HttpStatusCode::CREATED,
            HttpStatusCode::BAD_REQUEST,
        ];

        //! @section Act & Assert
        foreach ($testStatusCodes as $originalStatusCode) {
            $serialized = serialize($originalStatusCode);
            $this->assertIsString($serialized);

            $deserialized = unserialize($serialized);
            $this->assertSame($originalStatusCode, $deserialized);
            $this->assertSame($originalStatusCode->getValue(), $deserialized->getValue());
            $this->assertSame($originalStatusCode->getDescription(), $deserialized->getDescription());
        }
    }

    public function test_route_result_with_status_codes_serialization(): void
    {
        //! @section Act & Assert
        $result = new RouteResult(TemplateName::DEX, ['test' => 'data'], HttpStatusCode::CREATED);
        $serializedResult = serialize($result);
        $deserializedResult = unserialize($serializedResult);

        $this->assertSame($result->getTemplate(), $deserializedResult->getTemplate());
        $this->assertSame($result->getData(), $deserializedResult->getData());
        $this->assertSame($result->getStatusCode(), $deserializedResult->getStatusCode());
    }

    public function test_enum_performance_with_large_iterations(): void
    {
        //! @section Arrange
        $iterations = 1000;
        $startTime = microtime(true);

        //! @section Act - Perform many operations with status codes
        for ($i = 0; $i < $iterations; $i++) {
            $statusCode = HttpStatusCode::fromInt([200, 201, 202, 204][$i % 4]); // Cycle through some status codes
            $result = new RouteResult(TemplateName::HOME, [], $statusCode);
            $value = $result->getStatusCode()->getValue();
            $description = $result->getStatusCode()->getDescription();
            $isError = $result->getStatusCode()->isError();
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        //! @section Assert - Should complete in reasonable time (less than 1 second)
        $this->assertLessThan(1.0, $executionTime,
            "Enum operations should complete in reasonable time. Took {$executionTime}s for {$iterations} iterations");

        //! @section Assert - Verify the operations actually worked
        $this->assertGreaterThan(0, $executionTime, "Operations should take some time");
    }

    public function test_status_code_range_and_values(): void
    {
        //! @section Act & Assert
        $minStatusCode = HttpStatusCode::OK; // 200
        $maxStatusCode = HttpStatusCode::SERVICE_UNAVAILABLE; // 503

        $this->assertSame(200, $minStatusCode->getValue());
        $this->assertSame(503, $maxStatusCode->getValue());
    }

    public function test_all_status_codes_have_proper_descriptions(): void
    {
        //! @section Act & Assert
        $allStatusCodes = HttpStatusCode::cases();
        foreach ($allStatusCodes as $statusCode) {
            $description = $statusCode->getDescription();
            $this->assertNotEmpty($description, "Status code {$statusCode->value} should have description");
            $this->assertIsString($description);
            $this->assertStringNotContainsString('undefined', strtolower($description));
            $this->assertStringNotContainsString('unknown', strtolower($description));
        }
    }

    public function test_all_status_lines_are_properly_formatted(): void
    {
        //! @section Act & Assert
        $allStatusCodes = HttpStatusCode::cases();
        foreach ($allStatusCodes as $statusCode) {
            $statusLine = $statusCode->getStatusLine();
            $this->assertStringStartsWith((string)$statusCode->getValue(), $statusLine);
            $this->assertStringContainsString(' ', $statusLine);
            $this->assertStringContainsString($statusCode->getDescription(), $statusLine);
        }
    }

    public function test_enum_immutability_and_type_safety(): void
    {
        //! @section Act & Assert - Test that enums are immutable and type-safe

        $statusCode1 = HttpStatusCode::OK;
        $statusCode2 = HttpStatusCode::OK;
        $statusCode3 = HttpStatusCode::NOT_FOUND;

        $originalValue = $statusCode1->getValue();

        // Should be the same instance for same enum value
        $this->assertSame($statusCode1, $statusCode2);
        $this->assertSame($statusCode1, HttpStatusCode::OK);

        // Should be different instances for different enum values
        $this->assertNotSame($statusCode1, $statusCode3);

        // Should maintain type safety
        $this->assertInstanceOf(HttpStatusCode::class, $statusCode1);
        $this->assertInstanceOf(HttpStatusCode::class, $statusCode2);
        $this->assertInstanceOf(HttpStatusCode::class, $statusCode3);

        // Values should be readonly (can't be modified)
        $this->assertSame($originalValue, $statusCode1->getValue());

        // Should work in type hints
        $this->assertTrue($this->acceptsHttpStatusCode($statusCode1));
        $this->assertTrue($this->acceptsHttpStatusCode($statusCode3));
    }

    //! @brief Helper method to test type hinting
    private function acceptsHttpStatusCode(HttpStatusCode $statusCode): bool
    {
        return $statusCode instanceof HttpStatusCode;
    }
}
