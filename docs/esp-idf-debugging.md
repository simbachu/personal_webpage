# ESP-IDF HTTP Client Debugging Guide

This guide provides debugging endpoints and tools specifically designed for ESP-IDF HTTP client development.

## API Discovery

The API provides comprehensive discoverability through the base endpoint:

### `GET /api` - API Discovery
Primary discovery endpoint that provides complete API metadata in JSON format.

**Request:**
```bash
GET /api
```

**Response includes:**
- Complete endpoint listing with methods and descriptions
- Authentication requirements and methods
- JSON schema documentation
- ESP-IDF error code mappings
- Code examples and compatibility information

**Example Response:**
```json
{
  "api": {
    "name": "Sensor Batch API",
    "version": "1.0.0",
    "description": "REST API for collecting sensor data batches with IoT device support"
  },
  "endpoints": {
    "discovery": {
      "path": "/api",
      "methods": ["GET", "HEAD"],
      "description": "API discovery and metadata",
      "authentication": "none"
    },
    "data_submission": {
      "path": "/api/sensor/batch",
      "methods": ["POST"],
      "description": "Submit sensor data batches",
      "authentication": "required",
      "schema": {
        "batch_id": "string (unique identifier)",
        "generated_at": "integer (Unix timestamp)",
        "sensors": "array of sensor objects"
      }
    }
  },
  "authentication": {
    "required": true,
    "methods": {
      "basic": {
        "enabled": true,
        "format": "Authorization: Basic <base64(username:password)>",
        "esp_idf_example": "esp_http_client_set_header(client, \"Authorization\", \"Basic \" + base64(\"user:pass\"));"
      }
    }
  }
}
```

## Debug Endpoints

The API provides several debugging endpoints that return JSON responses optimized for ESP-IDF HTTP clients:

### `/api/debug`
General API information and status.

**Request:**
```bash
GET /api/debug
```

**Response:**
```json
{
  "api_name": "Sensor Batch API",
  "version": "1.0.0",
  "endpoints": {
    "POST /api/sensor/batch": "Submit sensor data batches",
    "HEAD /api": "Check API authentication",
    "HEAD /api/sensor/batch": "Check sensor endpoint authentication",
    "/api/debug/*": "Debugging endpoints for ESP-IDF"
  },
  "supported_methods": ["GET", "POST", "HEAD"],
  "authentication": {
    "methods": ["Bearer Token", "HTTP Basic Auth"],
    "configured": {
      "bearer": true,
      "basic": true
    },
    "required": true
  },
  "esp_idf_notes": {
    "basic_auth_format": "Authorization: Basic <base64(username:password)>",
    "content_type": "application/json",
    "error_codes": {
      "200": "Success",
      "401": "No authentication provided",
      "403": "Invalid authentication",
      "404": "Endpoint not found",
      "405": "Method not allowed",
      "422": "Invalid request data"
    }
  },
  "server_time": "2025-10-23T10:30:00+00:00",
  "request_info": {
    "method": "GET",
    "user_agent": "ESP-IDF/1.0",
    "remote_addr": "192.168.1.100"
  }
}
```

### `/api/debug/status`
Authentication status check with detailed feedback.

**Request:**
```bash
GET /api/debug/status
Authorization: Basic <base64_credentials>
```

**Response:**
```json
{
  "authenticated": false,
  "status_code": 403,
  "message": "Authentication failed",
  "authentication_methods": {
    "bearer_configured": true,
    "basic_configured": true,
    "attempted_method": "Basic",
    "provided_credentials": true
  },
  "esp_idf_tips": {
    "success": false,
    "message": "Authentication failed",
    "esp_idf_code": "ESP_ERR_HTTP_CONNECT",
    "tips": [
      "Verify your credentials are correct",
      "Check if the authentication method is enabled",
      "Use /api/debug/test-auth to debug authentication",
      "Ensure base64 encoding is correct for Basic auth"
    ]
  },
  "timestamp": "2025-10-23T10:30:00+00:00"
}
```

### `/api/debug/echo`
Echo back request details for debugging HTTP client configuration.

**Request:**
```bash
POST /api/debug/echo
Content-Type: application/json
Authorization: Bearer your-token

{"test": "data", "sensor_id": 1}
```

**Response:**
```json
{
  "echo": true,
  "method": "POST",
  "headers": {
    "Authorization": "Bearer your-token",
    "Content-Type": "application/json",
    "User-Agent": "ESP-IDF/1.0"
  },
  "body_length": 32,
  "body_preview": "{\"test\": \"data\", \"sensor_id\": 1}",
  "query_string": null,
  "content_type": "application/json",
  "content_length": "32",
  "esp_idf_debug": {
    "headers_count": 3,
    "json_valid": true,
    "suggestion": "Use this endpoint to verify your ESP-IDF HTTP client is sending data correctly"
  },
  "timestamp": "2025-10-23T10:30:00+00:00"
}
```

### `/api/debug/test-auth`
Comprehensive authentication testing for both methods.

**Request:**
```bash
GET /api/debug/test-auth
Authorization: Basic <base64_credentials>
```

**Response:**
```json
{
  "overall_result": "success",
  "status_code": 200,
  "method_tests": {
    "bearer": {
      "enabled": true,
      "provided": true,
      "valid": true,
      "reason": "Valid Bearer token"
    },
    "basic": {
      "enabled": true,
      "provided": false,
      "reason": "No Basic auth provided in request"
    }
  },
  "configuration": {
    "bearer_enabled": true,
    "basic_enabled": true
  },
  "esp_idf_examples": {
    "basic_auth": "esp_http_client_set_header(client, \"Authorization\", \"Basic \" + base64(\"user:pass\"));",
    "bearer_auth": "esp_http_client_set_header(client, \"Authorization\", \"Bearer your-token\");",
    "content_type": "esp_http_client_set_header(client, \"Content-Type\", \"application/json\");"
  },
  "timestamp": "2025-10-23T10:30:00+00:00"
}
```

## ESP-IDF HTTP Client Code Examples

### Basic Authentication
```c
// ESP-IDF HTTP Client with Basic Auth
esp_http_client_config_t config = {
    .url = "https://your-domain.com/api/sensor/batch",
    .method = HTTP_METHOD_POST,
    .cert_pem = NULL,
};

esp_http_client_handle_t client = esp_http_client_init(&config);

// Set Basic Auth header
char auth_header[128];
snprintf(auth_header, sizeof(auth_header), "Basic %s", base64_credentials);
esp_http_client_set_header(client, "Authorization", auth_header);

// Set Content-Type
esp_http_client_set_header(client, "Content-Type", "application/json");

// Set request body
const char *post_data = "{\"batch_id\":\"batch-1\",\"generated_at\":1758643200,\"sensors\":[{\"sensor_id\":1,\"measurements\":[{\"timestamp\":1758643200,\"temperature_c\":-19.8,\"humidity_pct\":41.2}]}]}";
esp_http_client_set_post_field(client, post_data, strlen(post_data));

// Perform request
esp_err_t err = esp_http_client_perform(client);
if (err == ESP_OK) {
    int status_code = esp_http_client_get_status_code(client);
    ESP_LOGI(TAG, "Status code: %d", status_code);

    if (status_code == 200) {
        ESP_LOGI(TAG, "Request successful");
    } else if (status_code == 401) {
        ESP_LOGE(TAG, "Authentication required - check credentials");
    } else if (status_code == 403) {
        ESP_LOGE(TAG, "Authentication failed - check credentials format");
    }
}

esp_http_client_cleanup(client);
```

### Using Debug Endpoints
```c
// Test authentication before sending data
esp_http_client_config_t debug_config = {
    .url = "https://your-domain.com/api/debug/test-auth",
    .method = HTTP_METHOD_GET,
};

esp_http_client_handle_t debug_client = esp_http_client_init(&debug_config);

// Set auth header for testing
esp_http_client_set_header(debug_client, "Authorization", auth_header);

esp_err_t debug_err = esp_http_client_perform(debug_client);
if (debug_err == ESP_OK) {
    int debug_status = esp_http_client_get_status_code(debug_client);
    if (debug_status == 200) {
        ESP_LOGI(TAG, "Authentication working correctly");
    } else {
        ESP_LOGE(TAG, "Authentication issue detected: %d", debug_status);
    }
}

esp_http_client_cleanup(debug_client);
```

## Error Code Mapping

The API maps HTTP status codes to ESP-IDF error codes for easier debugging:

| HTTP Code | ESP-IDF Code | Description |
|-----------|--------------|-------------|
| 200 | ESP_OK | Success |
| 400 | ESP_ERR_INVALID_ARG | Bad request/Invalid JSON |
| 401 | ESP_ERR_HTTP_HEADER | Authentication required |
| 403 | ESP_ERR_HTTP_CONNECT | Authentication failed |
| 404 | ESP_ERR_NOT_FOUND | Endpoint not found |
| 405 | ESP_ERR_NOT_SUPPORTED | Method not allowed |
| 422 | ESP_ERR_INVALID_RESPONSE | Invalid request data |
| 500 | ESP_FAIL | Server error |

## Common Issues and Solutions

### 1. "ESP_ERR_HTTP_HEADER" (401)
**Problem:** No authentication provided
**Solution:**
- Add Authorization header to your request
- Use `/api/debug/test-auth` to verify credential format

### 2. "ESP_ERR_HTTP_CONNECT" (403)
**Problem:** Invalid authentication credentials
**Solution:**
- Verify credentials match your .env configuration
- Check base64 encoding for Basic auth
- Use `/api/debug/test-auth` to debug authentication

### 3. "ESP_ERR_INVALID_ARG" (400)
**Problem:** Invalid JSON format
**Solution:**
- Verify JSON syntax in request body
- Use `/api/debug/echo` to test your JSON format
- Ensure Content-Type is `application/json`

### 4. "ESP_ERR_NOT_FOUND" (404)
**Problem:** Wrong endpoint URL
**Solution:**
- Use `/api/debug` to see available endpoints
- Check for typos in URL
- Verify HTTP method is supported

## Quick Debug Commands

```bash
# Discover API (recommended starting point)
curl https://your-domain.com/api

# Check API status
curl -I https://your-domain.com/api/debug

# Test authentication
curl -H "Authorization: Basic <base64>" https://your-domain.com/api/debug/test-auth

# Echo request for debugging
curl -X POST -H "Authorization: Bearer <token>" \
     -H "Content-Type: application/json" \
     -d '{"test": "data"}' \
     https://your-domain.com/api/debug/echo

# Check available endpoints
curl https://your-domain.com/api/debug
```

## ESP-IDF Client Integration

The API discovery endpoint makes it easy for ESP-IDF devices to dynamically discover API capabilities:

```c
// ESP-IDF example: Discover API before use
esp_http_client_config_t config = {
    .url = "https://your-domain.com/api",
    .method = HTTP_METHOD_GET,
};

esp_http_client_handle_t client = esp_http_client_init(&config);
esp_err_t err = esp_http_client_perform(client);

if (err == ESP_OK) {
    int status = esp_http_client_get_status_code(client);
    if (status == 200) {
        // Parse JSON response to discover available endpoints
        char response[1024];
        int len = esp_http_client_read(client, response, sizeof(response));
        // Parse JSON to find authentication requirements, endpoints, etc.
    }
}

esp_http_client_cleanup(client);
```

## Environment Configuration

Debug endpoints work regardless of authentication configuration. They will show you:

- Which authentication methods are enabled
- Whether credentials are correctly formatted
- ESP-IDF specific code examples
- Detailed error messages with solutions

See `env.example` for configuration options.
