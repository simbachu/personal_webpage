<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Api\SensorBatchController;

class ApiSensorBatchIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        // Set up test environment with a known bearer token
        $_ENV['API_BEARER_TOKEN'] = 'test-bearer-123';
    }

    protected function tearDown(): void
    {
        // Clean up environment
        unset($_ENV['API_BEARER_TOKEN']);
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
    }
}


