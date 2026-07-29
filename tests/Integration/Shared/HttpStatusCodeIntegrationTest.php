<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Type\HttpStatusCode;
use App\Type\TemplateName;
use App\Router\RouteResult;

//! @brief Integration test for HttpStatusCode enum with RouteResult
//!
//! Tests that the HttpStatusCode enum correctly integrates with RouteResult
//! and maintains proper functionality throughout the routing system.
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

    public function test_route_result_can_use_different_status_codes(): void
    {
        //! @section Act - Success codes
        $okResult = new RouteResult(TemplateName::HOME, [], HttpStatusCode::OK);
        $createdResult = new RouteResult(TemplateName::HOME, [], HttpStatusCode::CREATED);

        //! @section Assert - Success codes
        $this->assertSame(HttpStatusCode::OK, $okResult->getStatusCode());
        $this->assertTrue($okResult->getStatusCode()->isSuccess());
        $this->assertSame(HttpStatusCode::CREATED, $createdResult->getStatusCode());
        $this->assertTrue($createdResult->getStatusCode()->isSuccess());

        //! @section Act - Client error codes
        $notFoundResult = new RouteResult(TemplateName::NOT_FOUND, [], HttpStatusCode::NOT_FOUND);
        $badRequestResult = new RouteResult(TemplateName::NOT_FOUND, [], HttpStatusCode::BAD_REQUEST);

        //! @section Assert - Client error codes
        $this->assertSame(HttpStatusCode::NOT_FOUND, $notFoundResult->getStatusCode());
        $this->assertTrue($notFoundResult->getStatusCode()->isClientError());
        $this->assertTrue($notFoundResult->getStatusCode()->isError());
        $this->assertSame(HttpStatusCode::BAD_REQUEST, $badRequestResult->getStatusCode());
        $this->assertTrue($badRequestResult->getStatusCode()->isClientError());

        //! @section Act - Server error codes
        $serverErrorResult = new RouteResult(TemplateName::NOT_FOUND, [], HttpStatusCode::INTERNAL_SERVER_ERROR);

        //! @section Assert - Server error codes
        $this->assertSame(HttpStatusCode::INTERNAL_SERVER_ERROR, $serverErrorResult->getStatusCode());
        $this->assertTrue($serverErrorResult->getStatusCode()->isServerError());
        $this->assertTrue($serverErrorResult->getStatusCode()->isError());

        //! @section Act - Redirection codes
        $redirectResult = new RouteResult(TemplateName::HOME, [], HttpStatusCode::MOVED_PERMANENTLY);

        //! @section Assert - Redirection codes
        $this->assertSame(HttpStatusCode::MOVED_PERMANENTLY, $redirectResult->getStatusCode());
        $this->assertTrue($redirectResult->getStatusCode()->isRedirection());
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

    public function test_with_status_code_preserves_other_data(): void
    {
        //! @section Arrange
        $data = ['title' => 'Test Page', 'content' => 'Test Content'];
        $original = new RouteResult(TemplateName::DEX, $data, HttpStatusCode::OK);

        //! @section Act
        $modified = $original->withStatusCode(HttpStatusCode::INTERNAL_SERVER_ERROR);

        //! @section Assert
        $this->assertSame(TemplateName::DEX, $modified->getTemplate());
        $this->assertSame($data, $modified->getData());
        $this->assertSame(HttpStatusCode::INTERNAL_SERVER_ERROR, $modified->getStatusCode());
    }

    public function test_status_code_works_with_http_response_code_function(): void
    {
        //! @section Arrange
        $result = new RouteResult(TemplateName::NOT_FOUND, [], HttpStatusCode::NOT_FOUND);

        //! @section Act
        $statusCode = $result->getStatusCode()->getValue();

        //! @section Assert
        $this->assertIsInt($statusCode);
        $this->assertSame(404, $statusCode);

        // This simulates what happens in index.php
        // We can't actually call http_response_code() in tests without affecting output
        // but we can verify the value is correct
        $this->assertGreaterThanOrEqual(100, $statusCode);
        $this->assertLessThanOrEqual(599, $statusCode);
    }

    public function test_status_code_categories_work_correctly_in_context(): void
    {
        //! @section Arrange & Act & Assert - Test different status code categories

        // Success responses
        $successResult = new RouteResult(TemplateName::HOME, [], HttpStatusCode::OK);
        $this->assertTrue($successResult->getStatusCode()->isSuccess());
        $this->assertFalse($successResult->getStatusCode()->isError());
        $this->assertFalse($successResult->getStatusCode()->isClientError());
        $this->assertFalse($successResult->getStatusCode()->isServerError());
        $this->assertFalse($successResult->getStatusCode()->isRedirection());

        // Client error responses
        $clientErrorResult = new RouteResult(TemplateName::NOT_FOUND, [], HttpStatusCode::BAD_REQUEST);
        $this->assertFalse($clientErrorResult->getStatusCode()->isSuccess());
        $this->assertTrue($clientErrorResult->getStatusCode()->isError());
        $this->assertTrue($clientErrorResult->getStatusCode()->isClientError());
        $this->assertFalse($clientErrorResult->getStatusCode()->isServerError());
        $this->assertFalse($clientErrorResult->getStatusCode()->isRedirection());

        // Server error responses
        $serverErrorResult = new RouteResult(TemplateName::NOT_FOUND, [], HttpStatusCode::INTERNAL_SERVER_ERROR);
        $this->assertFalse($serverErrorResult->getStatusCode()->isSuccess());
        $this->assertTrue($serverErrorResult->getStatusCode()->isError());
        $this->assertFalse($serverErrorResult->getStatusCode()->isClientError());
        $this->assertTrue($serverErrorResult->getStatusCode()->isServerError());
        $this->assertFalse($serverErrorResult->getStatusCode()->isRedirection());

        // Redirection responses
        $redirectResult = new RouteResult(TemplateName::HOME, [], HttpStatusCode::MOVED_PERMANENTLY);
        $this->assertFalse($redirectResult->getStatusCode()->isSuccess());
        $this->assertFalse($redirectResult->getStatusCode()->isError());
        $this->assertFalse($redirectResult->getStatusCode()->isClientError());
        $this->assertFalse($redirectResult->getStatusCode()->isServerError());
        $this->assertTrue($redirectResult->getStatusCode()->isRedirection());
    }

    public function test_status_descriptions_are_meaningful_in_context(): void
    {
        //! @section Act - Test that descriptions make sense in HTTP context
        $notFoundResult = new RouteResult(TemplateName::NOT_FOUND, [], HttpStatusCode::NOT_FOUND);
        $serverErrorResult = new RouteResult(TemplateName::NOT_FOUND, [], HttpStatusCode::INTERNAL_SERVER_ERROR);
        $badRequestResult = new RouteResult(TemplateName::NOT_FOUND, [], HttpStatusCode::BAD_REQUEST);

        //! @section Assert - Test that descriptions make sense in HTTP context
        $this->assertSame('Not Found', $notFoundResult->getStatusCode()->getDescription());
        $this->assertSame('404 Not Found', $notFoundResult->getStatusCode()->getStatusLine());

        $this->assertSame('Internal Server Error', $serverErrorResult->getStatusCode()->getDescription());
        $this->assertSame('500 Internal Server Error', $serverErrorResult->getStatusCode()->getStatusLine());

        $this->assertSame('Bad Request', $badRequestResult->getStatusCode()->getDescription());
        $this->assertSame('400 Bad Request', $badRequestResult->getStatusCode()->getStatusLine());
    }

    public function test_enum_can_be_used_in_conditional_logic(): void
    {
        //! @section Arrange
        $results = [
            new RouteResult(TemplateName::HOME, [], HttpStatusCode::OK),
            new RouteResult(TemplateName::NOT_FOUND, [], HttpStatusCode::NOT_FOUND),
            new RouteResult(TemplateName::NOT_FOUND, [], HttpStatusCode::INTERNAL_SERVER_ERROR),
            new RouteResult(TemplateName::HOME, [], HttpStatusCode::MOVED_PERMANENTLY),
        ];

        //! @section Act
        $categorized = [
            'success' => [],
            'error' => [],
            'redirect' => []
        ];

        foreach ($results as $result) {
            $status = $result->getStatusCode();

            if ($status->isSuccess()) {
                $categorized['success'][] = $result;
            } elseif ($status->isError()) {
                $categorized['error'][] = $result;
            } elseif ($status->isRedirection()) {
                $categorized['redirect'][] = $result;
            }
        }

        //! @section Assert
        $this->assertCount(1, $categorized['success']);
        $this->assertCount(2, $categorized['error']);
        $this->assertCount(1, $categorized['redirect']);

        $this->assertSame(HttpStatusCode::OK, $categorized['success'][0]->getStatusCode());
        $this->assertContains($results[1]->getStatusCode(), [HttpStatusCode::NOT_FOUND]);
        $this->assertContains($results[2]->getStatusCode(), [HttpStatusCode::INTERNAL_SERVER_ERROR]);
        $this->assertSame(HttpStatusCode::MOVED_PERMANENTLY, $categorized['redirect'][0]->getStatusCode());
    }

    public function test_status_code_serialization_works_with_route_result(): void
    {
        //! @section Arrange
        $original = new RouteResult(TemplateName::DEX, ['test' => 'data'], HttpStatusCode::CREATED);

        //! @section Act
        $serialized = serialize($original);
        $unserialized = unserialize($serialized);

        //! @section Assert
        $this->assertSame($original->getTemplate(), $unserialized->getTemplate());
        $this->assertSame($original->getData(), $unserialized->getData());
        $this->assertSame($original->getStatusCode(), $unserialized->getStatusCode());
        $this->assertSame(HttpStatusCode::CREATED, $unserialized->getStatusCode());
    }

    public function test_status_code_from_int_integration(): void
    {
        //! @section Arrange
        $statusCodeInt = 404;

        //! @section Act
        $statusCode = HttpStatusCode::fromInt($statusCodeInt);
        $result = new RouteResult(TemplateName::NOT_FOUND, [], $statusCode);

        //! @section Assert
        $this->assertSame(HttpStatusCode::NOT_FOUND, $statusCode);
        $this->assertSame(HttpStatusCode::NOT_FOUND, $result->getStatusCode());
        $this->assertSame(404, $result->getStatusCode()->getValue());
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
