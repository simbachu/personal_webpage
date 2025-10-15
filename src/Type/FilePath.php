<?php

declare(strict_types=1);

namespace App\Type;

use InvalidArgumentException;

//! @brief Value object for file system paths with validation and manipulation
//!
//! This class encapsulates file system paths, providing validation to ensure
//! they are safe and well-formed, along with utilities for path manipulation.
//! It prevents path traversal attacks and provides a consistent interface
//! for working with file paths throughout the application.
//!
//! @code
//! // Example usage:
//! $path = FilePath::fromString('/var/www/cache');
//! echo $path->getValue(); // "/var/www/cache"
//!
//! // Path manipulation
//! $cacheFile = $path->join('pokemon_123.json');
//! echo $cacheFile->getValue(); // "/var/www/cache/pokemon_123.json"
//!
//! // Validation
//! $path->ensureDirectoryExists(); // Creates directory if it doesn't exist
//! @endcode
final class FilePath
{
    private readonly string $path; //!< The normalized file path

    //! @brief Constructor
    //! @param path The file path string
    //! @throws \InvalidArgumentException If the path is invalid
    private function __construct(string $path)
    {
        $this->validate($path);
        $this->path = $this->normalize($path);
    }

    //! @brief Validate that the file path is safe and well-formed
    //! @param path The path string to validate
    //! @throws \InvalidArgumentException If the path is invalid
    private function validate(string $path): void
    {
        $trimmedPath = trim($path);

        if (empty($trimmedPath)) {
            throw new InvalidArgumentException('File path cannot be empty.');
        }

        // Check for path traversal attacks (but allow legitimate relative paths)
        // Only reject sequences that are actually traversal attempts
        if (preg_match('/\.\.\//', $trimmedPath) || preg_match('/\.\//', $trimmedPath)) {
            throw new InvalidArgumentException('File path contains invalid traversal sequences.');
        }

        // Check for null bytes (potential security risk)
        if (str_contains($trimmedPath, "\0")) {
            throw new InvalidArgumentException('File path contains null bytes.');
        }

        // Check for overly long paths (Windows limit is ~260 chars, Unix is much higher)
        if (strlen($trimmedPath) > 2048) {
            throw new InvalidArgumentException('File path is too long (maximum 2048 characters).');
        }

        // Check for invalid characters (basic validation, but allow colons for Windows drives)
        if (preg_match('/[<>"|?*]/', $trimmedPath)) {
            throw new InvalidArgumentException('File path contains invalid characters.');
        }

        // Additional check for invalid colon usage (not at start for Windows drives)
        if (str_contains($trimmedPath, ':') && !preg_match('/^[A-Za-z]:/', $trimmedPath)) {
            throw new InvalidArgumentException('File path contains invalid colon usage.');
        }
    }

    //! @brief Normalize the file path
    //! @param path The raw path string
    //! @return string The normalized path
    private function normalize(string $path): string
    {
        $normalized = str_replace(['\\', '//'], ['/', '/'], $path);

        // Special case: don't rtrim root paths
        if ($normalized === '/' || preg_match('/^[A-Za-z]:\/$/', $normalized)) {
            return $normalized;
        }

        $normalized = rtrim($normalized, '/');

        // Handle relative paths by converting to absolute
        if (!str_starts_with($normalized, '/') && !preg_match('/^[A-Za-z]:/', $normalized)) {
            // This is a relative path, keep it as is for flexibility
            return $normalized;
        }

        return $normalized;
    }

    //! @brief Create a FilePath instance from a string
    //! @param path The string representation of the file path
    //! @return self A new FilePath instance
    //! @throws \InvalidArgumentException If the path is invalid
    public static function fromString(string $path): self
    {
        return new self($path);
    }

    //! @brief Get the string value of the file path
    //! @return string The file path
    public function getValue(): string
    {
        return $this->path;
    }

    //! @brief Check if this file path is equal to another file path
    //! @param other The other FilePath instance to compare with
    //! @return bool True if the file paths are identical
    public function equals(FilePath $other): bool
    {
        return $this->path === $other->path;
    }

    //! @brief Join this path with another path component
    //! @param component The path component to join (filename or subdirectory)
    //! @return self A new FilePath representing the joined path
    //! @throws \InvalidArgumentException If the component is invalid
    public function join(string $component): self
    {
        if (empty(trim($component))) {
            throw new InvalidArgumentException('Path component cannot be empty.');
        }

        // Remove leading/trailing slashes from component
        $cleanComponent = trim($component, '/');

        if (empty($cleanComponent)) {
            return $this;
        }

        $joinedPath = $this->path . '/' . $cleanComponent;
        return self::fromString($joinedPath);
    }

    //! @brief Get the directory portion of this path
    //! @return self A new FilePath representing the directory
    public function getDirectory(): self
    {
        $directory = dirname($this->path);

        // Handle special cases where dirname('/') returns different values on different systems
        if ($directory === '.' || $directory === '' || $directory === '\\') {
            $directory = '/';
        }

        // Handle Windows drive roots like 'C:\'
        if (preg_match('/^[A-Za-z]:$/', $this->path)) {
            $directory = $this->path . '/';
        }

        return self::fromString($directory);
    }

    //! @brief Get the filename portion of this path
    //! @return string The filename (including extension)
    public function getFilename(): string
    {
        return basename($this->path);
    }

    //! @brief Get the filename without extension
    //! @return string The filename without extension
    public function getFilenameWithoutExtension(): string
    {
        $filename = $this->getFilename();
        $extension = $this->getExtension();

        if ($extension === '') {
            return $filename;
        }

        return substr($filename, 0, -strlen('.' . $extension));
    }

    //! @brief Get the file extension
    //! @return string The file extension (without the dot)
    public function getExtension(): string
    {
        $filename = $this->getFilename();
        $lastDot = strrpos($filename, '.');

        // For hidden files like .env, we want to return the extension
        // Only return empty if there's no dot or the dot is the last character
        if ($lastDot === false || $lastDot === strlen($filename) - 1) {
            return '';
        }

        return substr($filename, $lastDot + 1);
    }

    //! @brief Check if this path exists in the file system
    //! @return bool True if the path exists
    public function exists(): bool
    {
        return file_exists($this->path);
    }

    //! @brief Check if this path is a directory
    //! @return bool True if the path exists and is a directory
    public function isDirectory(): bool
    {
        return is_dir($this->path);
    }

    //! @brief Check if this path is a file
    //! @return bool True if the path exists and is a file
    public function isFile(): bool
    {
        return is_file($this->path);
    }

    //! @brief Ensure the directory for this path exists, creating it if necessary
    //! @param permissions Directory permissions (defaults to 0777)
    //! @return bool True if the directory exists or was created successfully
    public function ensureDirectoryExists(int $permissions = 0777): bool
    {
        $directory = $this->getDirectory();

        if ($directory->exists() && $directory->isDirectory()) {
            return true;
        }

        return @mkdir($directory->getValue(), $permissions, true);
    }

    //! @brief Read the contents of this file
    //! @return string The file contents
    //! @throws \RuntimeException If the file cannot be read
    public function readContents(): string
    {
        if (!$this->exists() || !$this->isFile()) {
            throw new \RuntimeException("File does not exist or is not readable: {$this->path}");
        }

        $contents = file_get_contents($this->path);
        if ($contents === false) {
            throw new \RuntimeException("Failed to read file contents: {$this->path}");
        }

        return $contents;
    }

    //! @brief Write contents to this file
    //! @param contents The contents to write
    //! @param createDirectory Whether to create the directory if it doesn't exist
    //! @return bool True if the write was successful
    public function writeContents(string $contents, bool $createDirectory = true): bool
    {
        if ($createDirectory) {
            $this->ensureDirectoryExists();
        }

        return file_put_contents($this->path, $contents) !== false;
    }

    //! @brief Delete this file
    //! @return bool True if the file was deleted successfully or didn't exist
    public function delete(): bool
    {
        if (!$this->exists()) {
            return true;
        }

        return @unlink($this->path);
    }

    //! @brief Get the size of this file in bytes
    //! @return int The file size in bytes
    //! @throws \RuntimeException If the file doesn't exist or size cannot be determined
    public function getSize(): int
    {
        if (!$this->exists() || !$this->isFile()) {
            throw new \RuntimeException("File does not exist: {$this->path}");
        }

        $size = filesize($this->path);
        if ($size === false) {
            throw new \RuntimeException("Cannot determine file size: {$this->path}");
        }

        return $size;
    }

    //! @brief Get the last modified time of this file
    //! @return int The last modified time as a Unix timestamp
    //! @throws \RuntimeException If the file doesn't exist or time cannot be determined
    public function getLastModified(): int
    {
        if (!$this->exists()) {
            throw new \RuntimeException("File does not exist: {$this->path}");
        }

        $mtime = filemtime($this->path);
        if ($mtime === false) {
            throw new \RuntimeException("Cannot determine file modification time: {$this->path}");
        }

        return $mtime;
    }

    //! @brief Check if this file is older than the specified number of seconds
    //! @param seconds The number of seconds to check against
    //! @return bool True if the file is older than the specified time
    //! @throws \RuntimeException If the file doesn't exist or time cannot be determined
    public function isOlderThan(int $seconds): bool
    {
        $lastModified = $this->getLastModified();
        return (time() - $lastModified) > $seconds;
    }

    //! @brief Convert to string representation
    //! @return string The file path
    public function __toString(): string
    {
        return $this->path;
    }
}
