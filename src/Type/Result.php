<?php

declare(strict_types=1);

namespace App\Type;

//! @brief Generic Result type for handling success/failure states with explicit typing
//!
//! @tparam T The type of the success value
//!
//! Similar to C++'s std::expected<T, E> or Rust's Result<T, E>, this class provides
//! type-safe error handling without exceptions. The Result type encapsulates either
//! a successful value of type T or an error message string.
//!
//! @code
//! // Example usage:
//! $result = Result::success(42);
//! if ($result->isSuccess()) {
//!     echo $result->getValue(); // 42
//! }
//!
//! $errorResult = Result::failure("Something went wrong");
//! if ($errorResult->isFailure()) {
//!     echo $errorResult->getError(); // "Something went wrong"
//! }
//! @endcode
final class Result
{
    //! @brief Private constructor for Result instances
    //! @param value The success value (null for failure results)
    //! @param error The error message (null for success results)
    //! @throws \InvalidArgumentException If both value and error are provided or both are null
    private function __construct(
        private mixed $value = null,
        private ?string $error = null
    ) {
        // Ensure either value or error is set, but not both
        if (($value === null && $error === null) || ($value !== null && $error !== null)) {
            throw new \InvalidArgumentException('Result must have either a value or an error, but not both');
        }
    }

    //! @brief Create a successful Result with a value
    //! @tparam TValue The type of the success value
    //! @param value The success value
    //! @return Result<TValue> A successful Result containing the value
    public static function success(mixed $value): self
    {
        return new self(value: $value);
    }

    //! @brief Create a failed Result with an error message
    //! @tparam TValue The type that would have been returned on success
    //! @param error The error message describing the failure
    //! @return Result<TValue> A failed Result containing the error message
    public static function failure(string $error): self
    {
        return new self(error: $error);
    }

    //! @brief Check if this Result represents a success
    //! @return bool True if this Result contains a successful value, false if it contains an error
    public function isSuccess(): bool
    {
        return $this->error === null;
    }

    //! @brief Check if this Result represents a failure
    //! @return bool True if this Result contains an error, false if it contains a successful value
    public function isFailure(): bool
    {
        return $this->error !== null;
    }

    //! @brief Get the success value from this Result
    //! @return T The success value
    //! @throws \RuntimeException If this Result is a failure (contains an error)
    public function getValue(): mixed
    {
        if ($this->isFailure()) {
            throw new \RuntimeException('Cannot get value from failed Result: ' . $this->error);
        }
        return $this->value;
    }

    //! @brief Get the error message from this Result
    //! @return string The error message
    //! @throws \RuntimeException If this Result is a success (contains a value)
    public function getError(): string
    {
        if ($this->isSuccess()) {
            throw new \RuntimeException('Cannot get error from successful Result');
        }
        return $this->error;
    }

    //! @brief Get the success value or return a default value on failure
    //! @tparam TDefault The type of the default value
    //! @param default The default value to return if this Result is a failure
    //! @return T|TDefault The success value if successful, otherwise the default value
    public function getValueOrDefault(mixed $default): mixed
    {
        return $this->isSuccess() ? $this->value : $default;
    }

    //! @brief Transform the success value using a callback function
    //! @tparam TNew The type of the transformed value
    //! @param transform A function that takes the success value and returns a new value
    //! @return Result<TNew> A new Result with the transformed value, or the same error if this Result failed
    public function map(callable $transform): Result
    {
        if ($this->isFailure()) {
            return self::failure($this->error);
        }
        return self::success($transform($this->value));
    }

    //! @brief Transform the success value using a callback that returns a Result (monadic bind)
    //! @tparam TNew The type of the success value in the returned Result
    //! @param transform A function that takes the success value and returns a new Result
    //! @return Result<TNew> The flattened Result from the transformation, or the same error if this Result failed
    public function flatMap(callable $transform): Result
    {
        if ($this->isFailure()) {
            return self::failure($this->error);
        }
        return $transform($this->value);
    }

    //! @brief Handle both success and failure cases with appropriate handlers
    //! @tparam TReturn The return type of both handler functions
    //! @param onSuccess A function to call with the success value if this Result is successful
    //! @param onFailure A function to call with the error message if this Result is a failure
    //! @return TReturn The result of calling the appropriate handler function
    public function match(callable $onSuccess, callable $onFailure): mixed
    {
        if ($this->isSuccess()) {
            return $onSuccess($this->value);
        }
        return $onFailure($this->error);
    }

    //! @brief Execute a callback if this Result represents a success
    //! @param callback A function to call with the success value if this Result is successful
    //! @return Result<T> This Result unchanged (for method chaining)
    public function onSuccess(callable $callback): self
    {
        if ($this->isSuccess()) {
            $callback($this->value);
        }
        return $this;
    }

    //! @brief Execute a callback if this Result represents a failure
    //! @param callback A function to call with the error message if this Result is a failure
    //! @return Result<T> This Result unchanged (for method chaining)
    public function onFailure(callable $callback): self
    {
        if ($this->isFailure()) {
            $callback($this->error);
        }
        return $this;
    }

    //! @brief Convert to string representation for debugging purposes
    //! @return string A string representation showing whether this Result is success or failure
    public function __toString(): string
    {
        if ($this->isSuccess()) {
            return 'Result::success(' . (is_object($this->value) ? get_class($this->value) : gettype($this->value)) . ')';
        }
        return 'Result::failure(' . $this->error . ')';
    }
}
