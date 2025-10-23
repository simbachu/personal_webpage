<?php

declare(strict_types=1);

namespace App\Api;

use App\Type\HttpStatusCode;

//! @brief Controller for handling sensor batch API requests
class SensorBatchController
{
    private ?string $requiredBearer = null;
    private ?string $basicAuthUser = null;
    private ?string $basicAuthPass = null;

    //! @brief Constructor - loads authentication credentials from environment
    public function __construct()
    {
        $this->loadEnvironmentVariables();
        $this->requiredBearer = $_ENV['API_BEARER_TOKEN'] ?? null;
        $this->basicAuthUser = $_ENV['API_BASIC_AUTH_USER'] ?? null;
        $this->basicAuthPass = $_ENV['API_BASIC_AUTH_PASS'] ?? null;
    }

    //! @brief Handle the API request
    //! @param method HTTP method (e.g., "POST")
    //! @param headers Associative array of headers (case-insensitive keys acceptable)
    //! @param rawBody Raw request body string
    //! @return array{status:int, body:array} Status code and JSON-serializable body
    public function handle(string $method, array $headers, string $rawBody): array
    {
        // Allow HEAD method for authentication checking (like the /api endpoint)
        if (strtoupper($method) === 'HEAD') {
            $authResult = $this->checkAuthentication($headers);
            return [
                'status' => $authResult['status'],
                'body' => $authResult['body'] ?? []
            ];
        }

        if (strtoupper($method) !== 'POST') {
            return [
                'status' => HttpStatusCode::METHOD_NOT_ALLOWED->getValue(),
                'body' => $this->generateEspIdfFriendlyError(HttpStatusCode::METHOD_NOT_ALLOWED->getValue(), 'Method not allowed', [
                    'supported_methods' => ['POST', 'HEAD'],
                    'requested_method' => $method,
                    'endpoint' => '/api/sensor/batch',
                    'tip' => 'Use POST for submitting data or HEAD for authentication check'
                ])
            ];
        }

        $authResult = $this->checkAuthentication($headers);
        if ($authResult['status'] !== 200) {
            return [
                'status' => $authResult['status'],
                'body' => $authResult['body'] ?? $this->generateEspIdfFriendlyError($authResult['status'], 'Authentication failed', [
                    'tip' => 'Use /api/debug/test-auth to verify your credentials'
                ])
            ];
        }

        $data = json_decode($rawBody, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'status' => HttpStatusCode::BAD_REQUEST->getValue(),
                'body' => $this->generateEspIdfFriendlyError(HttpStatusCode::BAD_REQUEST->getValue(), 'Invalid JSON format', [
                    'json_error' => json_last_error_msg(),
                    'body_length' => strlen($rawBody),
                    'tip' => 'Use /api/debug/echo to validate your JSON format'
                ])
            ];
        }

        $schemaError = $this->validateSchema($data);
        if ($schemaError !== null) {
            return [
                'status' => HttpStatusCode::UNPROCESSABLE_ENTITY->getValue(),
                'body' => $this->generateEspIdfFriendlyError(HttpStatusCode::UNPROCESSABLE_ENTITY->getValue(), 'Schema validation failed', [
                    'validation_error' => $schemaError,
                    'expected_format' => [
                        'batch_id' => 'string (non-empty)',
                        'generated_at' => 'integer (timestamp)',
                        'sensors' => 'array of sensor objects',
                        'sensors[].sensor_id' => 'integer',
                        'sensors[].measurements' => 'array of measurement objects',
                        'sensors[].measurements[].timestamp' => 'integer',
                        'sensors[].measurements[].temperature_c' => 'number',
                        'sensors[].measurements[].humidity_pct' => 'number'
                    ],
                    'tip' => 'Check the API documentation for the exact schema requirements'
                ])
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

    //! @brief Check authentication and return appropriate status code
    //! @param headers Associative array of request headers
    //! @return array{status:int, body?:array} Status code and optional error body
    private function checkAuthentication(array $headers): array
    {
        // If no authentication is configured, always return 200
        if ($this->requiredBearer === null && ($this->basicAuthUser === null || $this->basicAuthPass === null)) {
            return ['status' => 200];
        }

        $authHeader = $this->getHeader($headers, 'Authorization');
        if ($authHeader === null) {
            return [
                'status' => 401, // No auth provided
                'body' => $this->generateEspIdfFriendlyError(401, 'Authentication required', [
                    'tip' => 'Use /api/debug/test-auth to verify your credentials format'
                ])
            ];
        }

        // Try HTTP Basic Auth first (for IoT devices)
        if (preg_match('/^Basic\s+(.+)$/i', $authHeader, $matches)) {
            return $this->checkBasicAuth($matches[1]);
        }

        // Fall back to Bearer token auth
        if (preg_match('/^Bearer\s+(\S+)/i', $authHeader, $matches)) {
            return $this->checkBearerAuth($matches[1]);
        }

        // Unknown auth format
        return [
            'status' => 403, // Auth provided but invalid format
            'body' => $this->generateEspIdfFriendlyError(403, 'Invalid authentication format', [
                'provided_format' => 'Unknown',
                'supported_formats' => ['Basic <base64>', 'Bearer <token>'],
                'tip' => 'Check Authorization header format in your ESP-IDF HTTP client'
            ])
        ];
    }

    //! @brief Check HTTP Basic Auth credentials
    //! @param credentials Base64-encoded username:password
    //! @return array{status:int, body?:array} Status code and optional error body
    private function checkBasicAuth(string $credentials): array
    {
        if ($this->basicAuthUser === null || $this->basicAuthPass === null) {
            return [
                'status' => 403, // Basic auth not configured but attempted
                'body' => $this->generateEspIdfFriendlyError(403, 'Basic authentication not configured', [
                    'configured_methods' => $this->requiredBearer ? ['Bearer'] : [],
                    'tip' => 'Configure API_BASIC_AUTH_USER and API_BASIC_AUTH_PASS in your .env file'
                ])
            ];
        }

        $decoded = base64_decode($credentials, true);
        if ($decoded === false) {
            return [
                'status' => 403, // Invalid base64
                'body' => $this->generateEspIdfFriendlyError(403, 'Invalid Basic auth encoding', [
                    'encoding_error' => 'Base64 decode failed',
                    'tip' => 'Ensure credentials are properly base64 encoded: base64(username:password)'
                ])
            ];
        }

        if (!str_contains($decoded, ':')) {
            return [
                'status' => 403, // Invalid format
                'body' => $this->generateEspIdfFriendlyError(403, 'Invalid Basic auth format', [
                    'format_error' => 'Missing colon separator',
                    'tip' => 'Format should be: username:password (before base64 encoding)'
                ])
            ];
        }

        [$username, $password] = explode(':', $decoded, 2);
        if ($username === $this->basicAuthUser && $password === $this->basicAuthPass) {
            return ['status' => 200]; // Valid credentials
        }

        return [
            'status' => 403, // Invalid credentials
            'body' => $this->generateEspIdfFriendlyError(403, 'Invalid Basic auth credentials', [
                'tip' => 'Verify username and password match your .env configuration'
            ])
        ];
    }

    //! @brief Check Bearer token
    //! @param token Bearer token to validate
    //! @return array{status:int, body?:array} Status code and optional error body
    private function checkBearerAuth(string $token): array
    {
        if ($this->requiredBearer === null) {
            return [
                'status' => 403, // Bearer auth not configured but attempted
                'body' => $this->generateEspIdfFriendlyError(403, 'Bearer authentication not configured', [
                    'configured_methods' => $this->basicAuthUser && $this->basicAuthPass ? ['Basic'] : [],
                    'tip' => 'Configure API_BEARER_TOKEN in your .env file'
                ])
            ];
        }

        if ($token === $this->requiredBearer) {
            return ['status' => 200]; // Valid token
        }

        return [
            'status' => 403, // Invalid token
            'body' => $this->generateEspIdfFriendlyError(403, 'Invalid Bearer token', [
                'tip' => 'Verify token matches API_BEARER_TOKEN in your .env file'
            ])
        ];
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

    //! @brief Generate ESP-IDF friendly error responses
    //! @param statusCode HTTP status code
    //! @param message Human readable error message
    //! @param details Additional error details
    //! @return array ESP-IDF friendly error response
    private function generateEspIdfFriendlyError(int $statusCode, string $message, array $details = []): array
    {
        $baseResponse = [
            'error' => true,
            'status_code' => $statusCode,
            'message' => $message,
            'esp_idf_mapping' => $this->mapStatusToEspIdf($statusCode),
            'timestamp' => date('c'),
            'endpoint' => '/api/sensor/batch'
        ];

        // Add specific tips based on status code
        $baseResponse['esp_idf_tips'] = $this->getStatusSpecificTips($statusCode);

        // Merge with additional details
        return array_merge($baseResponse, $details);
    }

    //! @brief Map HTTP status codes to ESP-IDF error codes
    //! @param statusCode HTTP status code
    //! @return string ESP-IDF error code mapping
    private function mapStatusToEspIdf(int $statusCode): string
    {
        return match($statusCode) {
            200 => 'ESP_OK',
            400 => 'ESP_ERR_INVALID_ARG',
            401 => 'ESP_ERR_HTTP_HEADER',
            403 => 'ESP_ERR_HTTP_CONNECT',
            404 => 'ESP_ERR_NOT_FOUND',
            405 => 'ESP_ERR_NOT_SUPPORTED',
            422 => 'ESP_ERR_INVALID_RESPONSE',
            500 => 'ESP_FAIL',
            default => 'ESP_FAIL'
        };
    }

    //! @brief Get status-specific tips for ESP-IDF developers
    //! @param statusCode HTTP status code
    //! @return array Tips for the specific error
    private function getStatusSpecificTips(int $statusCode): array
    {
        return match($statusCode) {
            400 => [
                'Check JSON syntax in your request body',
                'Verify Content-Type header is application/json',
                'Use /api/debug/echo to test your request format'
            ],
            401 => [
                'Add Authorization header to your request',
                'Use /api/debug/test-auth to verify your credentials',
                'Check if authentication is required for this endpoint'
            ],
            403 => [
                'Verify your credentials are correct',
                'Check if the authentication method is enabled',
                'Use /api/debug/test-auth to debug authentication',
                'Ensure base64 encoding is correct for Basic auth'
            ],
            404 => [
                'Verify the endpoint URL is correct',
                'Use /api/debug to see available endpoints',
                'Check HTTP method (GET, POST, HEAD)',
                'Remove trailing slashes from URL'
            ],
            405 => [
                'Check if HTTP method is supported',
                'Use /api/debug to see supported methods',
                'HEAD requests are supported for authentication checks'
            ],
            422 => [
                'Check JSON schema in your request',
                'Verify all required fields are present',
                'Use /api/debug/echo to validate your JSON'
            ],
            500 => [
                'Server internal error - check server logs',
                'Try again in a few moments',
                'Contact server administrator if persists'
            ],
            default => [
                'Check server logs for more details',
                'Use /api/debug endpoints for troubleshooting',
                'Verify your HTTP client configuration'
            ]
        };
    }
}


