<?php

declare(strict_types=1);

namespace App\Api;

use App\Type\HttpStatusCode;

//! @brief Controller for handling sensor batch API requests
class SensorBatchController
{
    private ?string $requiredBearer = null;

    //! @brief Constructor - loads bearer token from environment
    public function __construct()
    {
        $this->loadEnvironmentVariables();
        $this->requiredBearer = $_ENV['API_BEARER_TOKEN'] ?? null;
    }

    //! @brief Handle the API request
    //! @param method HTTP method (e.g., "POST")
    //! @param headers Associative array of headers (case-insensitive keys acceptable)
    //! @param rawBody Raw request body string
    //! @return array{status:int, body:array} Status code and JSON-serializable body
    public function handle(string $method, array $headers, string $rawBody): array
    {
        if (strtoupper($method) !== 'POST') {
            return [
                'status' => HttpStatusCode::METHOD_NOT_ALLOWED->getValue(),
                'body' => ['error' => 'Method Not Allowed']
            ];
        }

        $authHeader = $this->getHeader($headers, 'Authorization');
        if ($authHeader === null || !preg_match('/^Bearer\s+(\S+)/i', $authHeader, $m) || $m[1] !== $this->requiredBearer) {
            return [
                'status' => HttpStatusCode::UNAUTHORIZED->getValue(),
                'body' => ['error' => 'Unauthorized']
            ];
        }

        $data = json_decode($rawBody, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'status' => HttpStatusCode::BAD_REQUEST->getValue(),
                'body' => ['error' => 'Invalid JSON']
            ];
        }

        $schemaError = $this->validateSchema($data);
        if ($schemaError !== null) {
            return [
                'status' => HttpStatusCode::UNPROCESSABLE_ENTITY->getValue(),
                'body' => ['error' => 'Schema validation failed', 'detail' => $schemaError]
            ];
        }

        return [
            'status' => HttpStatusCode::OK->getValue(),
            'body' => ['status' => 'ok']
        ];
    }

    //! @brief Validate the request body against expected schema
    //! @param data Decoded JSON data
    //! @return string|null Error message if invalid, null if valid
    private function validateSchema(mixed $data): ?string
    {
        if (!is_array($data)) {
            return 'Root must be an object';
        }

        if (!isset($data['batch_id']) || !is_string($data['batch_id']) || $data['batch_id'] === '') {
            return 'batch_id must be a non-empty string';
        }

        if (!isset($data['generated_at']) || !is_int($data['generated_at'])) {
            return 'generated_at must be an integer';
        }

        if (!isset($data['sensors']) || !is_array($data['sensors'])) {
            return 'sensors must be an array';
        }

        foreach ($data['sensors'] as $idx => $sensor) {
            if (!is_array($sensor)) {
                return "sensors[$idx] must be an object";
            }
            if (!array_key_exists('sensor_id', $sensor) || !is_int($sensor['sensor_id'])) {
                return "sensors[$idx].sensor_id must be an integer";
            }
            if (!isset($sensor['measurements']) || !is_array($sensor['measurements'])) {
                return "sensors[$idx].measurements must be an array";
            }
            foreach ($sensor['measurements'] as $jdx => $measurement) {
                if (!is_array($measurement)) {
                    return "sensors[$idx].measurements[$jdx] must be an object";
                }
                if (!array_key_exists('timestamp', $measurement) || !is_int($measurement['timestamp'])) {
                    return "sensors[$idx].measurements[$jdx].timestamp must be an integer";
                }
                if (!array_key_exists('temperature_c', $measurement) || !is_numeric($measurement['temperature_c'])) {
                    return "sensors[$idx].measurements[$jdx].temperature_c must be a number";
                }
                if (!array_key_exists('humidity_pct', $measurement) || !is_numeric($measurement['humidity_pct'])) {
                    return "sensors[$idx].measurements[$jdx].humidity_pct must be a number";
                }
            }
        }

        return null;
    }

    //! @brief Retrieve header value case-insensitively
    private function getHeader(array $headers, string $name): ?string
    {
        foreach ($headers as $key => $value) {
            if (strcasecmp((string)$key, $name) === 0) {
                return is_array($value) ? (string)($value[0] ?? '') : (string)$value;
            }
        }
        return null;
    }

    //! @brief Load environment variables from .env file if it exists
    private function loadEnvironmentVariables(): void
    {
        // Use the same path resolution logic as index.php
        $is_dev = (basename(dirname(__DIR__, 2)) === 'dev');
        $base_path = $is_dev ? dirname(dirname(__DIR__, 2)) : dirname(__DIR__, 2);
        $env_prefix = $is_dev ? '/dev' : '';

        $envFile = $base_path . '/httpd.private' . $env_prefix . '/.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (str_starts_with(trim($line), '#')) {
                    continue; // Skip comments
                }
                if (str_contains($line, '=')) {
                    [$key, $value] = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value, " \t\n\r\0\x0B\"'");
                    $_ENV[$key] = $value;
                }
            }
        }
    }
}


