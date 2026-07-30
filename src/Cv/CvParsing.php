<?php

declare(strict_types=1);

namespace App\Cv;

use App\Shared\Support\Result;

//! @brief Shared helpers for parsing untyped CV JSON into typed values
final class CvParsing
{
    //! @brief Require a non-empty string field
    //! @param data Untyped associative array
    //! @param key Field name
    //! @param context Human-readable parse context for error messages
    //! @return Result<string>
    public static function requireString(array $data, string $key, string $context): Result
    {
        if (!array_key_exists($key, $data)) {
            return Result::failure("{$context} missing required field \"{$key}\"");
        }

        if (!is_string($data[$key]) || trim($data[$key]) === '') {
            return Result::failure("{$context} field \"{$key}\" must be a non-empty string");
        }

        return Result::success($data[$key]);
    }

    //! @brief Parse an optional string field (absent or null => null)
    //! @param data Untyped associative array
    //! @param key Field name
    //! @param context Human-readable parse context for error messages
    //! @return Result<?string>
    public static function optionalString(array $data, string $key, string $context): Result
    {
        if (!array_key_exists($key, $data) || $data[$key] === null) {
            return Result::success(null);
        }

        if (!is_string($data[$key])) {
            return Result::failure("{$context} field \"{$key}\" must be a string or null");
        }

        $trimmed = trim($data[$key]);
        return Result::success($trimmed === '' ? null : $data[$key]);
    }

    //! @brief Require a list field (defaults to empty when key is absent)
    //! @param data Untyped associative array
    //! @param key Field name
    //! @param context Human-readable parse context for error messages
    //! @return Result<list<mixed>>
    public static function optionalList(array $data, string $key, string $context): Result
    {
        if (!array_key_exists($key, $data) || $data[$key] === null) {
            return Result::success([]);
        }

        if (!is_array($data[$key]) || !array_is_list($data[$key])) {
            return Result::failure("{$context} field \"{$key}\" must be a list");
        }

        return Result::success($data[$key]);
    }

    //! @brief Parse a list of non-empty strings
    //! @param items Untyped list
    //! @param context Human-readable parse context for error messages
    //! @return Result<list<string>>
    public static function stringList(array $items, string $context): Result
    {
        if (!array_is_list($items)) {
            return Result::failure("{$context} must be a list of strings");
        }

        $strings = [];
        foreach ($items as $index => $item) {
            if (!is_string($item) || trim($item) === '') {
                return Result::failure("{$context}[{$index}] must be a non-empty string");
            }
            $strings[] = $item;
        }

        return Result::success($strings);
    }
}
