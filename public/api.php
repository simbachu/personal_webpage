<?php

declare(strict_types=1);

// Minimal front controller for API endpoints

// Resolve base path like index.php does
$is_dev = (basename(__DIR__) === 'dev');
$base_path = $is_dev ? dirname(dirname(__DIR__)) : dirname(__DIR__);
$env_prefix = $is_dev ? '/dev' : '';

$vendor_autoload = $base_path . '/httpd.private' . $env_prefix . '/vendor/autoload.php';
if (file_exists($vendor_autoload)) {
    require_once $vendor_autoload;
} else {
    // Fallback for test environments or when vendor is not available
    require_once $base_path . '/vendor/autoload.php';
}

use App\Api\SensorBatchController;

// Route handling
$requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Handle HEAD request for base /api endpoint (authentication check)
if ($requestUri === '/api' && strtoupper($method) === 'HEAD') {
    $result = handleApiHeadRequest();
    http_response_code($result['status']);
    // No body for HEAD requests
    exit;
}

// Handle HEAD request for /api/sensor/batch (authentication check)
if ($requestUri === '/api/sensor/batch' && strtoupper($method) === 'HEAD') {
    $result = handleSensorBatchHeadRequest();
    http_response_code($result['status']);
    header('Content-Type: application/json');
    if (isset($result['body'])) {
        echo json_encode($result['body']);
    }
    exit;
}

// Handle GET request for base /api endpoint (API discovery)
if ($requestUri === '/api' && strtoupper($method) === 'GET') {
    $result = handleApiDiscovery();
    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}

// Debug routes for ESP-IDF and development
if (str_starts_with($requestUri, '/api/debug')) {
    $result = handleDebugRoutes($requestUri, $method);
    http_response_code($result['status'] ?? 200);
    header('Content-Type: application/json');
    echo json_encode($result['body'] ?? []);
    exit;
}

// Route only /api/sensor/batch for other methods
if ($requestUri !== '/api/sensor/batch') {
    $errorResponse = generateEspIdfFriendlyError(404, 'Endpoint not found', [
        'requested_uri' => $requestUri,
        'method' => $method,
        'available_endpoints' => [
            'POST /api/sensor/batch' => 'Submit sensor data',
            'HEAD /api' => 'Check API authentication',
            'HEAD /api/sensor/batch' => 'Check sensor endpoint authentication',
            '/api/debug' => 'General API debugging info',
            '/api/debug/status' => 'Authentication status check',
            '/api/debug/echo' => 'Echo request details',
            '/api/debug/test-auth' => 'Test authentication methods'
        ],
        'esp_idf_tips' => [
            'Verify the URL is correct',
            'Check if the endpoint requires authentication',
            'Use /api/debug for available endpoints',
            'Ensure HTTP method is supported (GET, POST, HEAD)'
        ]
    ]);

    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode($errorResponse);
    exit;
}

// Collect headers in a portable way
if (function_exists('getallheaders')) {
    $headers = getallheaders();
} else {
    $headers = [];
    foreach ($_SERVER as $name => $value) {
        if (str_starts_with($name, 'HTTP_')) {
            $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
            $headers[$key] = $value;
        }
    }
}

$rawBody = file_get_contents('php://input') ?: '';

$controller = new SensorBatchController();
$result = $controller->handle($method, $headers, $rawBody);

http_response_code($result['status'] ?? 200);
header('Content-Type: application/json');
echo json_encode($result['body'] ?? []);

if (!function_exists('handleApiHeadRequest')) {
    //! @brief Handle HEAD request to /api endpoint for authentication checking
    //! @return array{status:int} Status code for the response
    function handleApiHeadRequest(): array
    {
        // Load environment variables like the controller does
        $is_dev = (basename(dirname(__DIR__)) === 'dev');
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

        // Check if any authentication method is configured
        $bearerToken = $_ENV['API_BEARER_TOKEN'] ?? null;
        $basicAuthUser = $_ENV['API_BASIC_AUTH_USER'] ?? null;
        $basicAuthPass = $_ENV['API_BASIC_AUTH_PASS'] ?? null;

        // If no authentication is configured, return 200 (API is accessible)
        if ($bearerToken === null && ($basicAuthUser === null || $basicAuthPass === null)) {
            return ['status' => 200];
        }

        // Get headers for authentication check
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        } else {
            $headers = [];
            foreach ($_SERVER as $name => $value) {
                if (str_starts_with($name, 'HTTP_')) {
                    $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                    $headers[$key] = $value;
                }
            }
        }

        return checkAuthentication($headers, $bearerToken, $basicAuthUser, $basicAuthPass);
    }
}

if (!function_exists('checkAuthentication')) {
    //! @brief Check authentication and return appropriate status code
    //! @param headers Request headers
    //! @param bearerToken Configured bearer token
    //! @param basicAuthUser Configured basic auth username
    //! @param basicAuthPass Configured basic auth password
    //! @return array{status:int} Status code (200, 401, or 403)
    function checkAuthentication(array $headers, ?string $bearerToken, ?string $basicAuthUser, ?string $basicAuthPass): array
{
    $authHeader = getHeader($headers, 'Authorization');

    // No auth header provided - 401 Unauthorized
    if ($authHeader === null) {
        return ['status' => 401];
    }

    // Check Basic Auth
    if (preg_match('/^Basic\s+(.+)$/i', $authHeader, $matches)) {
        if ($basicAuthUser === null || $basicAuthPass === null) {
            return ['status' => 403]; // Basic auth not configured but attempted
        }

        $decoded = base64_decode($matches[1], true);
        if ($decoded === false || !str_contains($decoded, ':')) {
            return ['status' => 403]; // Invalid format
        }

        [$username, $password] = explode(':', $decoded, 2);
        if ($username === $basicAuthUser && $password === $basicAuthPass) {
            return ['status' => 200]; // Valid credentials
        }
        return ['status' => 403]; // Invalid credentials
    }

    // Check Bearer token
    if (preg_match('/^Bearer\s+(\S+)/i', $authHeader, $matches)) {
        if ($bearerToken === null) {
            return ['status' => 403]; // Bearer auth not configured but attempted
        }

        if ($matches[1] === $bearerToken) {
            return ['status' => 200]; // Valid token
        }
        return ['status' => 403]; // Invalid token
    }

        // Unknown auth format
        return ['status' => 403];
    }
}

if (!function_exists('handleSensorBatchHeadRequest')) {
    //! @brief Handle HEAD request to /api/sensor/batch endpoint for authentication checking
    //! @return array{status:int, body?:array} Status code and optional error body
    function handleSensorBatchHeadRequest(): array
    {
        // Get headers for authentication check
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        } else {
            $headers = [];
            foreach ($_SERVER as $name => $value) {
                if (str_starts_with($name, 'HTTP_')) {
                    $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                    $headers[$key] = $value;
                }
            }
        }

        $controller = new SensorBatchController();
        return $controller->handle('HEAD', $headers, '');
    }
}

//! @brief Handle GET /api endpoint - API discovery information
//! @return array API discovery response
function handleApiDiscovery(): array
    {
        // Load environment for auth checking
        loadEnvironmentForDebug();

        $authConfigured = [
            'bearer' => !empty($_ENV['API_BEARER_TOKEN']),
            'basic' => !empty($_ENV['API_BASIC_AUTH_USER']) && !empty($_ENV['API_BASIC_AUTH_PASS'])
        ];

        return [
            'api' => [
                'name' => 'Sensor Batch API',
                'version' => '1.0.0',
                'description' => 'REST API for collecting sensor data batches with IoT device support',
                'documentation' => '/api/debug',
                'contact' => 'https://github.com/your-repo'
            ],
            'endpoints' => [
                'discovery' => [
                    'path' => '/api',
                    'methods' => ['GET', 'HEAD'],
                    'description' => 'API discovery and metadata',
                    'authentication' => 'none'
                ],
                'data_submission' => [
                    'path' => '/api/sensor/batch',
                    'methods' => ['POST'],
                    'description' => 'Submit sensor data batches',
                    'authentication' => 'required',
                    'content_type' => 'application/json',
                    'schema' => [
                        'batch_id' => 'string (unique identifier)',
                        'generated_at' => 'integer (Unix timestamp)',
                        'sensors' => 'array of sensor objects',
                        'sensors[].sensor_id' => 'integer',
                        'sensors[].measurements' => 'array of measurement objects',
                        'sensors[].measurements[].timestamp' => 'integer',
                        'sensors[].measurements[].temperature_c' => 'number',
                        'sensors[].measurements[].humidity_pct' => 'number'
                    ]
                ],
                'authentication_check' => [
                    'path' => '/api/sensor/batch',
                    'methods' => ['HEAD'],
                    'description' => 'Check authentication for data endpoint',
                    'authentication' => 'required'
                ],
                'debug' => [
                    'path' => '/api/debug',
                    'methods' => ['GET'],
                    'description' => 'General API debugging information',
                    'authentication' => 'none'
                ],
                'auth_status' => [
                    'path' => '/api/debug/status',
                    'methods' => ['GET'],
                    'description' => 'Authentication status with detailed feedback',
                    'authentication' => 'optional'
                ],
                'request_echo' => [
                    'path' => '/api/debug/echo',
                    'methods' => ['POST'],
                    'description' => 'Echo request details for debugging',
                    'authentication' => 'optional'
                ],
                'auth_testing' => [
                    'path' => '/api/debug/test-auth',
                    'methods' => ['GET'],
                    'description' => 'Comprehensive authentication testing',
                    'authentication' => 'optional'
                ]
            ],
            'authentication' => [
                'required' => array_filter($authConfigured) !== [],
                'methods' => [
                    'basic' => [
                        'enabled' => $authConfigured['basic'],
                        'description' => 'HTTP Basic Authentication',
                        'format' => 'Authorization: Basic <base64(username:password)>',
                        'esp_idf_example' => 'esp_http_client_set_header(client, "Authorization", "Basic " + base64("user:pass"));'
                    ],
                    'bearer' => [
                        'enabled' => $authConfigured['bearer'],
                        'description' => 'Bearer Token Authentication',
                        'format' => 'Authorization: Bearer <token>',
                        'esp_idf_example' => 'esp_http_client_set_header(client, "Authorization", "Bearer your-token");'
                    ]
                ],
                'headers' => [
                    'content_type' => 'application/json',
                    'user_agent' => 'ESP-IDF/1.0 or similar'
                ]
            ],
            'error_codes' => [
                '200' => [
                    'esp_idf' => 'ESP_OK',
                    'description' => 'Success'
                ],
                '400' => [
                    'esp_idf' => 'ESP_ERR_INVALID_ARG',
                    'description' => 'Bad request or invalid JSON'
                ],
                '401' => [
                    'esp_idf' => 'ESP_ERR_HTTP_HEADER',
                    'description' => 'Authentication required'
                ],
                '403' => [
                    'esp_idf' => 'ESP_ERR_HTTP_CONNECT',
                    'description' => 'Authentication failed or forbidden'
                ],
                '404' => [
                    'esp_idf' => 'ESP_ERR_NOT_FOUND',
                    'description' => 'Endpoint not found'
                ],
                '405' => [
                    'esp_idf' => 'ESP_ERR_NOT_SUPPORTED',
                    'description' => 'Method not allowed'
                ],
                '422' => [
                    'esp_idf' => 'ESP_ERR_INVALID_RESPONSE',
                    'description' => 'Invalid request data or schema validation failed'
                ]
            ],
            'esp_idf_compatibility' => [
                'json_only' => true,
                'utf8_encoding' => true,
                'max_response_size' => '10KB',
                'recommended_timeout' => '30 seconds',
                'debug_endpoints_available' => true,
                'authentication_flexible' => true,
                'error_responses_detailed' => true
            ],
            'examples' => [
                'basic_auth' => [
                    'description' => 'Using Basic authentication',
                    'headers' => [
                        'Authorization: Basic ' . base64_encode('username:password'),
                        'Content-Type: application/json'
                    ],
                    'body' => json_encode([
                        'batch_id' => 'batch-001',
                        'generated_at' => time(),
                        'sensors' => [
                            [
                                'sensor_id' => 1,
                                'measurements' => [
                                    [
                                        'timestamp' => time(),
                                        'temperature_c' => 23.5,
                                        'humidity_pct' => 65.2
                                    ]
                                ]
                            ]
                        ]
                    ], JSON_PRETTY_PRINT)
                ],
                'debug_status' => [
                    'description' => 'Check authentication status',
                    'url' => '/api/debug/status',
                    'method' => 'GET',
                    'response_includes' => [
                        'Authentication status',
                        'ESP-IDF error code mappings',
                        'Specific troubleshooting tips'
                    ]
                ]
            ],
            'server' => [
                'time' => date('c'),
                'timezone' => date_default_timezone_get(),
                'php_version' => PHP_VERSION,
                'accepts' => 'JSON requests with UTF-8 encoding'
            ]
        ];
    }

if (!function_exists('handleDebugRoutes')) {
    //! @brief Handle debug routes for ESP-IDF and development
    //! @param requestUri The requested URI
    //! @param method HTTP method
    //! @return array{status?:int, body:array} Response data
    function handleDebugRoutes(string $requestUri, string $method): array
    {
        // Load environment for auth checking
        loadEnvironmentForDebug();

        // Route to specific debug endpoints
        if ($requestUri === '/api/debug') {
            return handleApiDebug($method);
        }

        if ($requestUri === '/api/debug/status') {
            return handleApiStatus($method);
        }

        if ($requestUri === '/api/debug/echo') {
            return handleApiEcho($method);
        }

        if ($requestUri === '/api/debug/test-auth') {
            return handleTestAuth($method);
        }

        return [
            'status' => 404,
            'body' => [
                'error' => 'Debug endpoint not found',
                'available_endpoints' => [
                    '/api/debug',
                    '/api/debug/status',
                    '/api/debug/echo',
                    '/api/debug/test-auth'
                ],
                'documentation' => 'These endpoints help with ESP-IDF HTTP client debugging'
            ]
        ];
    }
}

//! @brief Load environment variables for debug endpoints
function loadEnvironmentForDebug(): void
    {
        // Use the same path resolution logic as the main API
        $is_dev = (basename(dirname(__DIR__)) === 'dev');
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

if (!function_exists('handleApiDebug')) {
    //! @brief Handle /api/debug endpoint - general API information
    //! @param method HTTP method
    //! @return array{status?:int, body:array} Response data
    function handleApiDebug(string $method): array
    {
        $authConfigured = [
            'bearer' => !empty($_ENV['API_BEARER_TOKEN']),
            'basic' => !empty($_ENV['API_BASIC_AUTH_USER']) && !empty($_ENV['API_BASIC_AUTH_PASS'])
        ];

        return [
            'body' => [
                'api_name' => 'Sensor Batch API',
                'version' => '1.0.0',
                'documentation' => 'https://github.com/your-repo',
                'endpoints' => [
                    'POST /api/sensor/batch' => 'Submit sensor data batches',
                    'HEAD /api' => 'Check API authentication',
                    'HEAD /api/sensor/batch' => 'Check sensor endpoint authentication',
                    '/api/debug/*' => 'Debugging endpoints for ESP-IDF'
                ],
                'supported_methods' => ['GET', 'POST', 'HEAD'],
                'authentication' => [
                    'methods' => ['Bearer Token', 'HTTP Basic Auth'],
                    'configured' => $authConfigured,
                    'required' => array_filter($authConfigured) !== []
                ],
                'esp_idf_notes' => [
                    'basic_auth_format' => 'Authorization: Basic <base64(username:password)>',
                    'content_type' => 'application/json',
                    'error_codes' => [
                        '200' => 'Success',
                        '401' => 'No authentication provided',
                        '403' => 'Invalid authentication',
                        '404' => 'Endpoint not found',
                        '405' => 'Method not allowed',
                        '422' => 'Invalid request data'
                    ]
                ],
                'server_time' => date('c'),
                'request_info' => [
                    'method' => $method,
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                    'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
                ]
            ]
        ];
    }
}

if (!function_exists('handleApiStatus')) {
    //! @brief Handle /api/debug/status endpoint - authentication status
    //! @param method HTTP method
    //! @return array{status?:int, body:array} Response data
    function handleApiStatus(string $method): array
    {
        $headers = getAllHeadersForDebug();
        $authResult = checkAuthenticationForDebug($headers);

        return [
            'body' => [
                'authenticated' => $authResult['status'] === 200,
                'status_code' => $authResult['status'],
                'message' => match($authResult['status']) {
                    200 => 'Authentication successful',
                    401 => 'No authentication provided',
                    403 => 'Authentication failed or method not configured',
                    default => 'Unknown authentication status'
                },
                'authentication_methods' => [
                    'bearer_configured' => !empty($_ENV['API_BEARER_TOKEN']),
                    'basic_configured' => !empty($_ENV['API_BASIC_AUTH_USER']) && !empty($_ENV['API_BASIC_AUTH_PASS']),
                    'attempted_method' => detectAuthMethod($headers),
                    'provided_credentials' => hasAuthHeader($headers)
                ],
                'esp_idf_tips' => getEspIdfTips($authResult['status']),
                'timestamp' => date('c')
            ]
        ];
    }
}

if (!function_exists('handleApiEcho')) {
    //! @brief Handle /api/debug/echo endpoint - echo request details
    //! @param method HTTP method
    //! @return array{status?:int, body:array} Response data
    function handleApiEcho(string $method): array
    {
        $headers = getAllHeadersForDebug();
        $body = file_get_contents('php://input') ?: '';

        return [
            'body' => [
                'echo' => true,
                'method' => $method,
                'headers' => $headers,
                'body_length' => strlen($body),
                'body_preview' => strlen($body) > 0 ? substr($body, 0, 100) . (strlen($body) > 100 ? '...' : '') : null,
                'query_string' => $_SERVER['QUERY_STRING'] ?? null,
                'content_type' => $_SERVER['CONTENT_TYPE'] ?? null,
                'content_length' => $_SERVER['CONTENT_LENGTH'] ?? null,
                'esp_idf_debug' => [
                    'headers_count' => count($headers),
                    'json_valid' => json_decode($body) !== null || $body === '',
                    'suggestion' => 'Use this endpoint to verify your ESP-IDF HTTP client is sending data correctly'
                ],
                'timestamp' => date('c')
            ]
        ];
    }
}

if (!function_exists('handleTestAuth')) {
    //! @brief Handle /api/debug/test-auth endpoint - test authentication methods
    //! @param method HTTP method
    //! @return array{status?:int, body:array} Response data
    function handleTestAuth(string $method): array
    {
        $headers = getAllHeadersForDebug();
        $authResult = checkAuthenticationForDebug($headers);

        // Test both methods individually
        $bearerTest = testBearerAuth($headers);
        $basicTest = testBasicAuth($headers);

        return [
            'body' => [
                'overall_result' => $authResult['status'] === 200 ? 'success' : 'failed',
                'status_code' => $authResult['status'],
                'method_tests' => [
                    'bearer' => $bearerTest,
                    'basic' => $basicTest
                ],
                'configuration' => [
                    'bearer_enabled' => !empty($_ENV['API_BEARER_TOKEN']),
                    'basic_enabled' => !empty($_ENV['API_BASIC_AUTH_USER']) && !empty($_ENV['API_BASIC_AUTH_PASS'])
                ],
                'esp_idf_examples' => [
                    'basic_auth' => 'esp_http_client_set_header(client, "Authorization", "Basic " + base64("user:pass"));',
                    'bearer_auth' => 'esp_http_client_set_header(client, "Authorization", "Bearer your-token");',
                    'content_type' => 'esp_http_client_set_header(client, "Content-Type", "application/json");'
                ],
                'timestamp' => date('c')
            ]
        ];
    }
}

if (!function_exists('getAllHeadersForDebug')) {
    //! @brief Get all headers for debug endpoints
    //! @return array Headers array
    function getAllHeadersForDebug(): array
    {
        if (function_exists('getallheaders')) {
            return getallheaders();
        }

        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (str_starts_with($name, 'HTTP_')) {
                $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                $headers[$key] = $value;
            }
        }
        return $headers;
    }
}

if (!function_exists('checkAuthenticationForDebug')) {
    //! @brief Check authentication for debug endpoints
    //! @param headers Request headers
    //! @return array{status:int, body?:array} Authentication result
    function checkAuthenticationForDebug(array $headers): array
    {
        $bearerToken = $_ENV['API_BEARER_TOKEN'] ?? null;
        $basicAuthUser = $_ENV['API_BASIC_AUTH_USER'] ?? null;
        $basicAuthPass = $_ENV['API_BASIC_AUTH_PASS'] ?? null;

        // If no authentication is configured, always return 200
        if ($bearerToken === null && ($basicAuthUser === null || $basicAuthPass === null)) {
            return ['status' => 200];
        }

        $authHeader = getHeader($headers, 'Authorization');
        if ($authHeader === null) {
            return ['status' => 401];
        }

        // Check Basic Auth
        if (preg_match('/^Basic\s+(.+)$/i', $authHeader, $matches)) {
            return checkBasicAuthForDebug($matches[1], $basicAuthUser, $basicAuthPass);
        }

        // Check Bearer token
        if (preg_match('/^Bearer\s+(\S+)/i', $authHeader, $matches)) {
            return checkBearerAuthForDebug($matches[1], $bearerToken);
        }

        return ['status' => 403];
    }
}

if (!function_exists('checkBasicAuthForDebug')) {
    //! @brief Check Basic Auth for debug
    //! @param credentials Base64 credentials
    //! @param user Expected username
    //! @param pass Expected password
    //! @return array{status:int} Result
    function checkBasicAuthForDebug(string $credentials, ?string $user, ?string $pass): array
    {
        if ($user === null || $pass === null) {
            return ['status' => 403]; // Basic auth not configured
        }

        $decoded = base64_decode($credentials, true);
        if ($decoded === false || !str_contains($decoded, ':')) {
            return ['status' => 403]; // Invalid format
        }

        [$username, $password] = explode(':', $decoded, 2);
        if ($username === $user && $password === $pass) {
            return ['status' => 200];
        }

        return ['status' => 403]; // Invalid credentials
    }
}

if (!function_exists('checkBearerAuthForDebug')) {
    //! @brief Check Bearer Auth for debug
    //! @param token Provided token
    //! @param expectedToken Expected token
    //! @return array{status:int} Result
    function checkBearerAuthForDebug(string $token, ?string $expectedToken): array
    {
        if ($expectedToken === null) {
            return ['status' => 403]; // Bearer auth not configured
        }

        if ($token === $expectedToken) {
            return ['status' => 200];
        }

        return ['status' => 403]; // Invalid token
    }
}

if (!function_exists('detectAuthMethod')) {
    //! @brief Detect which auth method was attempted
    //! @param headers Request headers
    //! @return string|null Auth method or null
    function detectAuthMethod(array $headers): ?string
    {
        $authHeader = getHeader($headers, 'Authorization');
        if ($authHeader === null) {
            return null;
        }

        if (preg_match('/^Basic\s+/i', $authHeader)) {
            return 'Basic';
        }

        if (preg_match('/^Bearer\s+/i', $authHeader)) {
            return 'Bearer';
        }

        return 'Unknown';
    }
}

if (!function_exists('hasAuthHeader')) {
    //! @brief Check if auth header is present
    //! @param headers Request headers
    //! @return bool True if auth header present
    function hasAuthHeader(array $headers): bool
    {
        return getHeader($headers, 'Authorization') !== null;
    }
}

if (!function_exists('testBearerAuth')) {
    //! @brief Test Bearer auth method
    //! @param headers Request headers
    //! @return array Test result
    function testBearerAuth(array $headers): array
    {
        $token = $_ENV['API_BEARER_TOKEN'] ?? null;
        $authHeader = getHeader($headers, 'Authorization');

        if (empty($token)) {
            return [
                'enabled' => false,
                'reason' => 'Bearer token not configured in environment'
            ];
        }

        if ($authHeader === null || !preg_match('/^Bearer\s+(\S+)/i', $authHeader, $matches)) {
            return [
                'enabled' => true,
                'provided' => false,
                'reason' => 'No Bearer token provided in request'
            ];
        }

        if ($matches[1] === $token) {
            return [
                'enabled' => true,
                'provided' => true,
                'valid' => true,
                'reason' => 'Valid Bearer token'
            ];
        }

        return [
            'enabled' => true,
            'provided' => true,
            'valid' => false,
            'reason' => 'Invalid Bearer token'
        ];
    }
}

if (!function_exists('testBasicAuth')) {
    //! @brief Test Basic auth method
    //! @param headers Request headers
    //! @return array Test result
    function testBasicAuth(array $headers): array
    {
        $user = $_ENV['API_BASIC_AUTH_USER'] ?? null;
        $pass = $_ENV['API_BASIC_AUTH_PASS'] ?? null;
        $authHeader = getHeader($headers, 'Authorization');

        if (empty($user) || empty($pass)) {
            return [
                'enabled' => false,
                'reason' => 'Basic auth credentials not configured in environment'
            ];
        }

        if ($authHeader === null || !preg_match('/^Basic\s+(.+)$/i', $authHeader, $matches)) {
            return [
                'enabled' => true,
                'provided' => false,
                'reason' => 'No Basic auth provided in request'
            ];
        }

        $decoded = base64_decode($matches[1], true);
        if ($decoded === false || !str_contains($decoded, ':')) {
            return [
                'enabled' => true,
                'provided' => true,
                'valid' => false,
                'reason' => 'Invalid Basic auth format'
            ];
        }

        [$username, $password] = explode(':', $decoded, 2);
        if ($username === $user && $password === $pass) {
            return [
                'enabled' => true,
                'provided' => true,
                'valid' => true,
                'reason' => 'Valid Basic auth credentials'
            ];
        }

        return [
            'enabled' => true,
            'provided' => true,
            'valid' => false,
            'reason' => 'Invalid Basic auth credentials'
        ];
    }
}

if (!function_exists('getEspIdfTips')) {
    //! @brief Get ESP-IDF specific tips based on status code
    //! @param statusCode HTTP status code
    //! @return array Tips for ESP-IDF developers
    function getEspIdfTips(int $statusCode): array
    {
        return match($statusCode) {
            200 => [
                'success' => true,
                'message' => 'Authentication successful!',
                'esp_idf_code' => 'ESP_OK',
                'next_steps' => 'You can now send POST requests to /api/sensor/batch'
            ],
            401 => [
                'success' => false,
                'message' => 'No authentication provided',
                'esp_idf_code' => 'ESP_ERR_HTTP_HEADER',
                'tips' => [
                    'Add Authorization header to your request',
                    'For Basic auth: esp_http_client_set_header(client, "Authorization", "Basic <base64>");',
                    'For Bearer: esp_http_client_set_header(client, "Authorization", "Bearer <token>");'
                ]
            ],
            403 => [
                'success' => false,
                'message' => 'Authentication failed',
                'esp_idf_code' => 'ESP_ERR_HTTP_CONNECT',
                'tips' => [
                    'Check your credentials are correct',
                    'Verify the authentication method is enabled in server config',
                    'Ensure base64 encoding is correct for Basic auth',
                    'Check token format for Bearer auth'
                ]
            ],
            default => [
                'success' => false,
                'message' => 'Unknown status code',
                'esp_idf_code' => 'ESP_FAIL',
                'tips' => ['Check server logs for more details']
            ]
        };
    }
}

if (!function_exists('generateEspIdfFriendlyError')) {
    //! @brief Generate ESP-IDF friendly error responses
    //! @param statusCode HTTP status code
    //! @param message Human readable error message
    //! @param details Additional error details
    //! @return array ESP-IDF friendly error response
    function generateEspIdfFriendlyError(int $statusCode, string $message, array $details = []): array
    {
        $baseResponse = [
            'error' => true,
            'status_code' => $statusCode,
            'message' => $message,
            'esp_idf_mapping' => mapStatusToEspIdf($statusCode),
            'timestamp' => date('c'),
            'request_info' => [
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'Unknown',
                'uri' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
            ]
        ];

        // Add specific tips based on status code
        $baseResponse['esp_idf_tips'] = getStatusSpecificTips($statusCode);

        // Merge with additional details
        return array_merge($baseResponse, $details);
    }
}

if (!function_exists('mapStatusToEspIdf')) {
    //! @brief Map HTTP status codes to ESP-IDF error codes
    //! @param statusCode HTTP status code
    //! @return string ESP-IDF error code mapping
    function mapStatusToEspIdf(int $statusCode): string
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
}

if (!function_exists('getStatusSpecificTips')) {
    //! @brief Get status-specific tips for ESP-IDF developers
    //! @param statusCode HTTP status code
    //! @return array Tips for the specific error
    function getStatusSpecificTips(int $statusCode): array
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

if (!function_exists('getHeader')) {
    //! @brief Retrieve header value case-insensitively
    function getHeader(array $headers, string $name): ?string
    {
        foreach ($headers as $key => $value) {
            if (strcasecmp((string)$key, $name) === 0) {
                return is_array($value) ? (string)($value[0] ?? '') : (string)$value;
            }
        }
        return null;
    }
}


