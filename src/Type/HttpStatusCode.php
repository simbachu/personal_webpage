<?php

declare(strict_types=1);

namespace App\Type;

//! @brief Enum representing HTTP status codes used in routing responses
//!
//! This enum provides type safety for HTTP status codes, preventing typos and ensuring
//! only valid status codes can be used. Includes common HTTP status codes with their
//! corresponding numeric values and descriptions.
//!
//! @code
//! // Example usage:
//! $status = HttpStatusCode::OK;
//! echo $status->value; // 200
//! echo $status->getDescription(); // "OK"
//!
//! // Use in routing
//! $routeResult = new RouteResult(
//!     TemplateName::HOME,
//!     ['title' => 'Home'],
//!     HttpStatusCode::OK
//! );
//!
//! // Use in error handling
//! $errorResult = new RouteResult(
//!     TemplateName::NOT_FOUND,
//!     ['message' => 'Page not found'],
//!     HttpStatusCode::NOT_FOUND
//! );
//! @endcode
enum HttpStatusCode: int
{
    // 2xx Success
    case OK = 200;
    case CREATED = 201;
    case ACCEPTED = 202;
    case NO_CONTENT = 204;

    // 3xx Redirection
    case MOVED_PERMANENTLY = 301;
    case FOUND = 302;
    case SEE_OTHER = 303;
    case NOT_MODIFIED = 304;

    // 4xx Client Error
    case BAD_REQUEST = 400;
    case UNAUTHORIZED = 401;
    case FORBIDDEN = 403;
    case NOT_FOUND = 404;
    case METHOD_NOT_ALLOWED = 405;
    case CONFLICT = 409;
    case UNPROCESSABLE_ENTITY = 422;

    // 5xx Server Error
    case INTERNAL_SERVER_ERROR = 500;
    case NOT_IMPLEMENTED = 501;
    case BAD_GATEWAY = 502;
    case SERVICE_UNAVAILABLE = 503;

    //! @brief Get all valid status codes as an array
    //! @return int[] Array of all status code values
    public static function getAllValues(): array
    {
        return array_column(self::cases(), 'value');
    }

    //! @brief Check if an integer represents a valid status code
    //! @param statusCode The status code integer to validate
    //! @return bool True if the status code is valid
    public static function isValid(int $statusCode): bool
    {
        return in_array($statusCode, self::getAllValues(), true);
    }

    //! @brief Create HttpStatusCode from integer with validation
    //! @param statusCode The status code integer
    //! @return self The corresponding HttpStatusCode enum case
    //! @throws \InvalidArgumentException If the status code is not valid
    public static function fromInt(int $statusCode): self
    {
        return match ($statusCode) {
            200 => self::OK,
            201 => self::CREATED,
            202 => self::ACCEPTED,
            204 => self::NO_CONTENT,
            301 => self::MOVED_PERMANENTLY,
            302 => self::FOUND,
            303 => self::SEE_OTHER,
            304 => self::NOT_MODIFIED,
            400 => self::BAD_REQUEST,
            401 => self::UNAUTHORIZED,
            403 => self::FORBIDDEN,
            404 => self::NOT_FOUND,
            405 => self::METHOD_NOT_ALLOWED,
            409 => self::CONFLICT,
            422 => self::UNPROCESSABLE_ENTITY,
            500 => self::INTERNAL_SERVER_ERROR,
            501 => self::NOT_IMPLEMENTED,
            502 => self::BAD_GATEWAY,
            503 => self::SERVICE_UNAVAILABLE,
            default => throw new \InvalidArgumentException(
                "Invalid HTTP status code: {$statusCode}. Valid codes are: " . implode(', ', self::getAllValues())
            ),
        };
    }

    //! @brief Get a human-readable description of this status code
    //! @return string Description of what this status code represents
    public function getDescription(): string
    {
        return match ($this) {
            // 2xx Success
            self::OK => 'OK',
            self::CREATED => 'Created',
            self::ACCEPTED => 'Accepted',
            self::NO_CONTENT => 'No Content',

            // 3xx Redirection
            self::MOVED_PERMANENTLY => 'Moved Permanently',
            self::FOUND => 'Found',
            self::SEE_OTHER => 'See Other',
            self::NOT_MODIFIED => 'Not Modified',

            // 4xx Client Error
            self::BAD_REQUEST => 'Bad Request',
            self::UNAUTHORIZED => 'Unauthorized',
            self::FORBIDDEN => 'Forbidden',
            self::NOT_FOUND => 'Not Found',
            self::METHOD_NOT_ALLOWED => 'Method Not Allowed',
            self::CONFLICT => 'Conflict',
            self::UNPROCESSABLE_ENTITY => 'Unprocessable Entity',

            // 5xx Server Error
            self::INTERNAL_SERVER_ERROR => 'Internal Server Error',
            self::NOT_IMPLEMENTED => 'Not Implemented',
            self::BAD_GATEWAY => 'Bad Gateway',
            self::SERVICE_UNAVAILABLE => 'Service Unavailable',
        };
    }

    //! @brief Check if this status code represents a successful response
    //! @return bool True if this is a 2xx status code
    public function isSuccess(): bool
    {
        return $this->value >= 200 && $this->value < 300;
    }

    //! @brief Check if this status code represents a client error
    //! @return bool True if this is a 4xx status code
    public function isClientError(): bool
    {
        return $this->value >= 400 && $this->value < 500;
    }

    //! @brief Check if this status code represents a server error
    //! @return bool True if this is a 5xx status code
    public function isServerError(): bool
    {
        return $this->value >= 500 && $this->value < 600;
    }

    //! @brief Check if this status code represents a redirection
    //! @return bool True if this is a 3xx status code
    public function isRedirection(): bool
    {
        return $this->value >= 300 && $this->value < 400;
    }

    //! @brief Check if this status code represents an error (4xx or 5xx)
    //! @return bool True if this is an error status code
    public function isError(): bool
    {
        return $this->isClientError() || $this->isServerError();
    }

    //! @brief Convert to string representation
    //! @return string The status code value as string
    public function toString(): string
    {
        return (string)$this->value;
    }

    //! @brief Backward-compatible accessor matching other value objects
    //! @return int The raw status code value
    public function getValue(): int
    {
        return $this->value;
    }

    //! @brief Get the status line for HTTP response headers
    //! @return string Status line (e.g., "200 OK")
    public function getStatusLine(): string
    {
        return $this->value . ' ' . $this->getDescription();
    }
}
