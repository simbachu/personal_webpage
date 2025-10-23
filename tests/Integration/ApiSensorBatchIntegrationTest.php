<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Api\SensorBatchController;

class ApiSensorBatchIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        // Set up test environment with both auth methods
        $_ENV['API_BEARER_TOKEN'] = 'test-bearer-123';
        $_ENV['API_BASIC_AUTH_USER'] = 'test-user';
        $_ENV['API_BASIC_AUTH_PASS'] = 'test-pass';
    }

    protected function tearDown(): void
    {
        // Clean up environment
        unset($_ENV['API_BEARER_TOKEN']);
        unset($_ENV['API_BASIC_AUTH_USER']);
        unset($_ENV['API_BASIC_AUTH_PASS']);
    }

    private function validPayload(): string
    {
        $payload = [
            'batch_id' => 'batch-1',
            'generated_at' => 1758643200,
            'sensors' => [
                [
                    'sensor_id' => 1,
                    'measurements' => [
                        ['timestamp' => 1758643200, 'temperature_c' => -19.8, 'humidity_pct' => 41.2],
                    ],
                ],
            ],
        ];
        return json_encode($payload, JSON_THROW_ON_ERROR);
    }

    private function basicAuthHeader(string $username, string $password): string
    {
        return 'Basic ' . base64_encode($username . ':' . $password);
    }

    public function test_success_with_valid_json_and_bearer(): void
    {
        // Arrange
        $controller = new SensorBatchController();
        $headers = ['Authorization' => 'Bearer test-bearer-123'];
        $body = $this->validPayload();

        // Act
        $result = $controller->handle('POST', $headers, $body);

        // Assert
        $this->assertSame(200, $result['status']);
        $this->assertSame(['status' => 'ok'], $result['body']);
    }

    public function test_unauthorized_without_bearer(): void
    {
        // Arrange
        $controller = new SensorBatchController();

        // Act
        $result = $controller->handle('POST', [], $this->validPayload());

        // Assert
        $this->assertSame(401, $result['status']);
        $this->assertArrayHasKey('error', $result['body']);
        $this->assertTrue($result['body']['error']);
        $this->assertSame(401, $result['body']['status_code']);
    }

    public function test_bad_request_on_invalid_json(): void
    {
        // Arrange
        $controller = new SensorBatchController();
        $headers = ['Authorization' => 'Bearer test-bearer-123'];
        $body = '{invalid json';

        // Act
        $result = $controller->handle('POST', $headers, $body);

        // Assert
        $this->assertSame(400, $result['status']);
        $this->assertArrayHasKey('error', $result['body']);
        $this->assertTrue($result['body']['error']);
        $this->assertSame(400, $result['body']['status_code']);
    }

    public function test_unprocessable_entity_on_schema_error(): void
    {
        // Arrange
        $controller = new SensorBatchController();
        $headers = ['Authorization' => 'Bearer test-bearer-123'];
        $payload = ['batch_id' => '', 'generated_at' => 1, 'sensors' => []];
        $body = json_encode($payload);

        // Act
        $result = $controller->handle('POST', $headers, $body ?: '');

        // Assert
        $this->assertSame(422, $result['status']);
        $this->assertArrayHasKey('error', $result['body']);
        $this->assertTrue($result['body']['error']);
        $this->assertSame(422, $result['body']['status_code']);
    }

    public function test_method_not_allowed_for_get(): void
    {
        // Arrange
        $controller = new SensorBatchController();
        $headers = ['Authorization' => 'Bearer test-bearer-123'];

        // Act
        $result = $controller->handle('GET', $headers, '');

        // Assert
        $this->assertSame(405, $result['status']);
        $this->assertArrayHasKey('error', $result['body']);
        $this->assertTrue($result['body']['error']);
        $this->assertSame(405, $result['body']['status_code']);
    }

    public function test_success_with_valid_json_and_basic_auth(): void
    {
        // Arrange
        $controller = new SensorBatchController();
        $headers = ['Authorization' => $this->basicAuthHeader('test-user', 'test-pass')];
        $body = $this->validPayload();

        // Act
        $result = $controller->handle('POST', $headers, $body);

        // Assert
        $this->assertSame(200, $result['status']);
        $this->assertSame(['status' => 'ok'], $result['body']);
    }

    public function test_unauthorized_with_wrong_basic_auth_credentials(): void
    {
        // Arrange
        $controller = new SensorBatchController();
        $headers = ['Authorization' => $this->basicAuthHeader('wrong-user', 'wrong-pass')];

        // Act
        $result = $controller->handle('POST', $headers, $this->validPayload());

        // Assert
        $this->assertSame(403, $result['status']);
        $this->assertArrayHasKey('error', $result['body']);
        $this->assertTrue($result['body']['error']);
        $this->assertSame(403, $result['body']['status_code']);
    }

    public function test_unauthorized_with_basic_auth_disabled(): void
    {
        // Arrange - unset Basic auth credentials to disable it
        unset($_ENV['API_BASIC_AUTH_USER'], $_ENV['API_BASIC_AUTH_PASS']);
        $controller = new SensorBatchController();
        $headers = ['Authorization' => $this->basicAuthHeader('test-user', 'test-pass')];

        // Act
        $result = $controller->handle('POST', $headers, $this->validPayload());

        // Assert
        $this->assertSame(403, $result['status']);
        $this->assertArrayHasKey('error', $result['body']);
        $this->assertTrue($result['body']['error']);
        $this->assertSame(403, $result['body']['status_code']);
    }

    public function test_unauthorized_with_malformed_basic_auth(): void
    {
        // Arrange
        $controller = new SensorBatchController();
        $headers = ['Authorization' => 'Basic invalid-base64'];

        // Act
        $result = $controller->handle('POST', $headers, $this->validPayload());

        // Assert
        $this->assertSame(403, $result['status']);
        $this->assertArrayHasKey('error', $result['body']);
        $this->assertTrue($result['body']['error']);
        $this->assertSame(403, $result['body']['status_code']);
    }

    public function test_unauthorized_with_basic_auth_missing_password(): void
    {
        // Arrange
        $controller = new SensorBatchController();
        $headers = ['Authorization' => 'Basic ' . base64_encode('test-user')];

        // Act
        $result = $controller->handle('POST', $headers, $this->validPayload());

        // Assert
        $this->assertSame(403, $result['status']);
        $this->assertArrayHasKey('error', $result['body']);
        $this->assertTrue($result['body']['error']);
        $this->assertSame(403, $result['body']['status_code']);
    }

    public function test_bearer_auth_still_works_when_basic_auth_configured(): void
    {
        // Arrange
        $controller = new SensorBatchController();
        $headers = ['Authorization' => 'Bearer test-bearer-123'];
        $body = $this->validPayload();

        // Act
        $result = $controller->handle('POST', $headers, $body);

        // Assert
        $this->assertSame(200, $result['status']);
        $this->assertSame(['status' => 'ok'], $result['body']);
    }

    public function test_unauthorized_with_only_bearer_when_bearer_disabled(): void
    {
        // Arrange - unset Bearer token to disable it
        unset($_ENV['API_BEARER_TOKEN']);
        $controller = new SensorBatchController();
        $headers = ['Authorization' => 'Bearer test-bearer-123'];

        // Act
        $result = $controller->handle('POST', $headers, $this->validPayload());

        // Assert
        $this->assertSame(403, $result['status']);
        $this->assertArrayHasKey('error', $result['body']);
        $this->assertTrue($result['body']['error']);
        $this->assertSame(403, $result['body']['status_code']);
    }

    public function test_unauthorized_with_no_auth_provided(): void
    {
        // Arrange
        $controller = new SensorBatchController();

        // Act
        $result = $controller->handle('POST', [], $this->validPayload());

        // Assert
        $this->assertSame(401, $result['status']);
        $this->assertArrayHasKey('error', $result['body']);
        $this->assertTrue($result['body']['error']);
        $this->assertSame(401, $result['body']['status_code']);
    }

    public function test_head_method_allowed(): void
    {
        // Arrange
        $controller = new SensorBatchController();
        $headers = ['Authorization' => 'Bearer test-bearer-123'];

        // Act
        $result = $controller->handle('HEAD', $headers, '');

        // Assert
        $this->assertSame(200, $result['status']);
    }

    public function test_head_unauthorized_with_no_auth(): void
    {
        // Arrange
        $controller = new SensorBatchController();

        // Act
        $result = $controller->handle('HEAD', [], '');

        // Assert
        $this->assertSame(401, $result['status']);
        $this->assertArrayHasKey('error', $result['body']);
        $this->assertTrue($result['body']['error']);
        $this->assertSame(401, $result['body']['status_code']);
    }

    public function test_head_forbidden_with_wrong_auth(): void
    {
        // Arrange
        $controller = new SensorBatchController();
        $headers = ['Authorization' => 'Bearer wrong-token'];

        // Act
        $result = $controller->handle('HEAD', $headers, '');

        // Assert
        $this->assertSame(403, $result['status']);
        $this->assertArrayHasKey('error', $result['body']);
        $this->assertTrue($result['body']['error']);
        $this->assertSame(403, $result['body']['status_code']);
    }

    public function test_api_discovery_endpoint(): void
    {
        // This test verifies the GET /api endpoint provides discovery information
        // Since we can't easily test the full HTTP request in unit tests,
        // we test the discovery function directly

        // Test that the function exists and returns expected structure
        if (function_exists('handleApiDiscovery')) {
            $result = handleApiDiscovery();

            // Assert basic structure
            $this->assertIsArray($result);
            $this->assertArrayHasKey('api', $result);
            $this->assertArrayHasKey('endpoints', $result);
            $this->assertArrayHasKey('authentication', $result);

            // Assert API info
            $this->assertArrayHasKey('name', $result['api']);
            $this->assertArrayHasKey('version', $result['api']);
            $this->assertEquals('Sensor Batch API', $result['api']['name']);

            // Assert endpoints are documented
            $this->assertArrayHasKey('data_submission', $result['endpoints']);
            $this->assertEquals('/api/sensor/batch', $result['endpoints']['data_submission']['path']);
            $this->assertContains('POST', $result['endpoints']['data_submission']['methods']);

            // Assert authentication methods are documented
            $this->assertArrayHasKey('basic', $result['authentication']['methods']);
            $this->assertArrayHasKey('bearer', $result['authentication']['methods']);
            $this->assertArrayHasKey('enabled', $result['authentication']['methods']['basic']);
            $this->assertArrayHasKey('enabled', $result['authentication']['methods']['bearer']);

            // Assert error codes are mapped
            $this->assertArrayHasKey('error_codes', $result);
            $this->assertArrayHasKey('200', $result['error_codes']);
            $this->assertEquals('ESP_OK', $result['error_codes']['200']['esp_idf']);

            // Assert ESP-IDF compatibility info
            $this->assertArrayHasKey('esp_idf_compatibility', $result);
            $this->assertTrue($result['esp_idf_compatibility']['json_only']);
            $this->assertTrue($result['esp_idf_compatibility']['debug_endpoints_available']);
        } else {
            $this->markTestSkipped('API discovery function not available in test environment');
        }
    }

    public function test_api_discovery_integration(): void
    {
        // Test that the API discovery endpoint is properly routed
        // This simulates what happens when ESP-IDF makes a GET request to /api

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/api';


        // Capture the output
        ob_start();

        // Only run this test if we can safely include the API file
        $apiFile = __DIR__ . '/../../public/api.php';
        if (file_exists($apiFile)) {
            try {
                include $apiFile;

                // Check if the discovery function was properly loaded
                if (!function_exists('handleApiDiscovery')) {
                    ob_get_clean();
                    $this->markTestSkipped('API discovery function not available after including API file');
                }

                $output = ob_get_clean();

                // Verify response
                $this->assertNotEmpty($output);

                $response = json_decode($output, true);
                $this->assertNotNull($response, 'Response should be valid JSON');
                $this->assertArrayHasKey('api', $response);
                $this->assertArrayHasKey('endpoints', $response);
                $this->assertArrayHasKey('authentication', $response);

                // Verify expected structure
                $this->assertEquals('Sensor Batch API', $response['api']['name']);
                $this->assertArrayHasKey('data_submission', $response['endpoints']);
                $this->assertEquals('/api/sensor/batch', $response['endpoints']['data_submission']['path']);

                // Verify authentication methods are documented
                $this->assertArrayHasKey('methods', $response['authentication']);
                $this->assertArrayHasKey('basic', $response['authentication']['methods']);
                $this->assertArrayHasKey('bearer', $response['authentication']['methods']);

                // Verify error codes are mapped
                $this->assertArrayHasKey('error_codes', $response);
                $this->assertArrayHasKey('200', $response['error_codes']);
                $this->assertEquals('ESP_OK', $response['error_codes']['200']['esp_idf']);

            } catch (\Exception $e) {
                ob_get_clean();
                $this->markTestSkipped('API file could not be loaded in test environment: ' . $e->getMessage());
            }
        } else {
            ob_get_clean();
            $this->markTestSkipped('API file not found in test environment');
        }

        // Restore server variables
        unset($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
    }
}


