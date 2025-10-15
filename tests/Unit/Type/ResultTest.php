<?php

declare(strict_types=1);

namespace Tests\Unit\Type;

use PHPUnit\Framework\TestCase;
use App\Type\Result;

final class ResultTest extends TestCase
{
    //! @brief Test creating a successful Result
    public function test_creates_successful_result(): void
    {
        //! @section Arrange
        $value = 'test value';

        //! @section Act
        $result = Result::success($value);

        //! @section Assert
        $this->assertTrue($result->isSuccess());
        $this->assertFalse($result->isFailure());
        $this->assertSame($value, $result->getValue());
    }

    //! @brief Test creating a failed Result
    public function test_creates_failed_result(): void
    {
        //! @section Arrange
        $error = 'test error';

        //! @section Act
        $result = Result::failure($error);

        //! @section Assert
        $this->assertFalse($result->isSuccess());
        $this->assertTrue($result->isFailure());
        $this->assertSame($error, $result->getError());
    }

    //! @brief Test getting value from failed Result throws exception
    public function test_get_value_from_failed_result_throws_exception(): void
    {
        //! @section Arrange
        $result = Result::failure('error message');

        //! @section Act & Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot get value from failed Result: error message');
        $result->getValue();
    }

    //! @brief Test getting error from successful Result throws exception
    public function test_get_error_from_successful_result_throws_exception(): void
    {
        //! @section Arrange
        $result = Result::success('value');

        //! @section Act & Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot get error from successful Result');
        $result->getError();
    }

    //! @brief Test getValueOrDefault returns value for success
    public function test_get_value_or_default_returns_value_for_success(): void
    {
        //! @section Arrange
        $value = 'test value';
        $default = 'default value';
        $result = Result::success($value);

        //! @section Act
        $actual = $result->getValueOrDefault($default);

        //! @section Assert
        $this->assertSame($value, $actual);
    }

    //! @brief Test getValueOrDefault returns default for failure
    public function test_get_value_or_default_returns_default_for_failure(): void
    {
        //! @section Arrange
        $default = 'default value';
        $result = Result::failure('error');

        //! @section Act
        $actual = $result->getValueOrDefault($default);

        //! @section Assert
        $this->assertSame($default, $actual);
    }

    //! @brief Test map transforms successful Result
    public function test_map_transforms_successful_result(): void
    {
        //! @section Arrange
        $result = Result::success(5);
        $transform = fn(int $x): string => "Value: {$x}";

        //! @section Act
        $mapped = $result->map($transform);

        //! @section Assert
        $this->assertTrue($mapped->isSuccess());
        $this->assertSame('Value: 5', $mapped->getValue());
    }

    //! @brief Test map preserves failure
    public function test_map_preserves_failure(): void
    {
        //! @section Arrange
        $result = Result::failure('error message');
        $transform = fn(mixed $x): mixed => $x;

        //! @section Act
        $mapped = $result->map($transform);

        //! @section Assert
        $this->assertTrue($mapped->isFailure());
        $this->assertSame('error message', $mapped->getError());
    }

    //! @brief Test flatMap chains Results
    public function test_flat_map_chains_results(): void
    {
        //! @section Arrange
        $result = Result::success(5);
        $transform = fn(int $x): Result => Result::success($x * 2);

        //! @section Act
        $chained = $result->flatMap($transform);

        //! @section Assert
        $this->assertTrue($chained->isSuccess());
        $this->assertSame(10, $chained->getValue());
    }

    //! @brief Test flatMap preserves failure
    public function test_flat_map_preserves_failure(): void
    {
        //! @section Arrange
        $result = Result::failure('error message');
        $transform = fn(mixed $x): Result => Result::success($x);

        //! @section Act
        $chained = $result->flatMap($transform);

        //! @section Assert
        $this->assertTrue($chained->isFailure());
        $this->assertSame('error message', $chained->getError());
    }

    //! @brief Test match handles success case
    public function test_match_handles_success_case(): void
    {
        //! @section Arrange
        $result = Result::success('test');
        $onSuccess = fn(string $value): string => "Success: {$value}";
        $onFailure = fn(string $error): string => "Failure: {$error}";

        //! @section Act
        $actual = $result->match($onSuccess, $onFailure);

        //! @section Assert
        $this->assertSame('Success: test', $actual);
    }

    //! @brief Test match handles failure case
    public function test_match_handles_failure_case(): void
    {
        //! @section Arrange
        $result = Result::failure('error');
        $onSuccess = fn(string $value): string => "Success: {$value}";
        $onFailure = fn(string $error): string => "Failure: {$error}";

        //! @section Act
        $actual = $result->match($onSuccess, $onFailure);

        //! @section Assert
        $this->assertSame('Failure: error', $actual);
    }

    //! @brief Test onSuccess executes callback for success
    public function test_on_success_executes_callback_for_success(): void
    {
        //! @section Arrange
        $result = Result::success('test');
        $executed = false;
        $callback = function(string $value) use (&$executed): void {
            $executed = true;
            $this->assertSame('test', $value);
        };

        //! @section Act
        $returned = $result->onSuccess($callback);

        //! @section Assert
        $this->assertTrue($executed);
        $this->assertSame($result, $returned);
    }

    //! @brief Test onSuccess does not execute callback for failure
    public function test_on_success_does_not_execute_callback_for_failure(): void
    {
        //! @section Arrange
        $result = Result::failure('error');
        $executed = false;
        $callback = function(mixed $value) use (&$executed): void {
            $executed = true;
        };

        //! @section Act
        $returned = $result->onSuccess($callback);

        //! @section Assert
        $this->assertFalse($executed);
        $this->assertSame($result, $returned);
    }

    //! @brief Test onFailure executes callback for failure
    public function test_on_failure_executes_callback_for_failure(): void
    {
        //! @section Arrange
        $result = Result::failure('error');
        $executed = false;
        $callback = function(string $error) use (&$executed): void {
            $executed = true;
            $this->assertSame('error', $error);
        };

        //! @section Act
        $returned = $result->onFailure($callback);

        //! @section Assert
        $this->assertTrue($executed);
        $this->assertSame($result, $returned);
    }

    //! @brief Test onFailure does not execute callback for success
    public function test_on_failure_does_not_execute_callback_for_success(): void
    {
        //! @section Arrange
        $result = Result::success('test');
        $executed = false;
        $callback = function(string $error) use (&$executed): void {
            $executed = true;
        };

        //! @section Act
        $returned = $result->onFailure($callback);

        //! @section Assert
        $this->assertFalse($executed);
        $this->assertSame($result, $returned);
    }

    //! @brief Test string representation for success
    public function test_string_representation_for_success(): void
    {
        //! @section Arrange
        $result = Result::success('test');

        //! @section Act
        $string = (string) $result;

        //! @section Assert
        $this->assertStringContainsString('Result::success', $string);
        $this->assertStringContainsString('string', $string);
    }

    //! @brief Test string representation for failure
    public function test_string_representation_for_failure(): void
    {
        //! @section Arrange
        $result = Result::failure('error message');

        //! @section Act
        $string = (string) $result;

        //! @section Assert
        $this->assertStringContainsString('Result::failure', $string);
        $this->assertStringContainsString('error message', $string);
    }

    //! @brief Test constructor validation - both value and error
    public function test_constructor_throws_when_both_value_and_error_provided(): void
    {
        //! @section Act & Assert
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Call to private App\Type\Result::__construct()');
        new Result(value: 'test', error: 'error');
    }

    //! @brief Test constructor validation - neither value nor error
    public function test_constructor_throws_when_neither_value_nor_error_provided(): void
    {
        //! @section Act & Assert
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Call to private App\Type\Result::__construct()');
        new Result();
    }
}
