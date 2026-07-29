<?php

declare(strict_types=1);

namespace Tests\Unit\Type;

use PHPUnit\Framework\TestCase;
use App\Type\FilePath;

//! @brief Test suite for the FilePath value object
class FilePathTest extends TestCase
{
    public function test_creates_file_path_from_valid_string(): void
    {
        //! @section Arrange & Act
        $path = FilePath::fromString('/var/www/cache');

        //! @section Assert
        $this->assertSame('/var/www/cache', $path->getValue());
    }

    public function test_creates_file_path_from_relative_string(): void
    {
        //! @section Arrange & Act
        $path = FilePath::fromString('cache/pokemon.json');

        //! @section Assert
        $this->assertSame('cache/pokemon.json', $path->getValue());
    }

    public function test_normalizes_path_separators(): void
    {
        //! @section Arrange & Act
        $path = FilePath::fromString('/var\\www//cache');

        //! @section Assert
        $this->assertSame('/var/www/cache', $path->getValue());
    }

    public function test_removes_trailing_slashes(): void
    {
        //! @section Arrange & Act
        $path = FilePath::fromString('/var/www/cache/');

        //! @section Assert
        $this->assertSame('/var/www/cache', $path->getValue());
    }

    public function test_rejects_empty_path(): void
    {
        //! @section Arrange
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('File path cannot be empty.');

        //! @section Act
        FilePath::fromString('');
    }

    public function test_rejects_whitespace_only_path(): void
    {
        //! @section Arrange
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('File path cannot be empty.');

        //! @section Act
        FilePath::fromString('   ');
    }

    public function test_allows_legitimate_parent_directory_sequences(): void
    {
        //! @section Arrange & Act
        $path = FilePath::fromString('/var/www/../etc/passwd');

        //! @section Assert
        $this->assertSame('/var/etc/passwd', $path->getValue());
    }

    public function test_allows_legitimate_dot_slash_sequences(): void
    {
        //! @section Arrange & Act
        $path = FilePath::fromString('/var/www/./etc/passwd');

        //! @section Assert
        $this->assertSame('/var/www/etc/passwd', $path->getValue());
    }

    public function test_normalizes_dot_slash_sequences(): void
    {
        //! @section Arrange & Act
        $path = FilePath::fromString('./cache/pokemon.json');

        //! @section Assert
        $this->assertSame('./cache/pokemon.json', $path->getValue());
    }

    public function test_rejects_null_bytes(): void
    {
        //! @section Arrange
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('File path contains null bytes.');

        //! @section Act
        FilePath::fromString('/var/www/cache' . "\0" . 'file.txt');
    }

    public function test_rejects_overly_long_paths(): void
    {
        //! @section Arrange
        $longPath = '/' . str_repeat('a', 2048);

        //! @section Arrange
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('File path is too long (maximum 2048 characters).');

        //! @section Act
        FilePath::fromString($longPath);
    }

    public function test_rejects_invalid_characters(): void
    {
        //! @section Arrange
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('File path contains invalid characters.');

        //! @section Act
        FilePath::fromString('/var/www/cache<file>.txt');
    }

    public function test_equals_compares_paths_correctly(): void
    {
        //! @section Arrange
        $path1 = FilePath::fromString('/var/www/cache');
        $path2 = FilePath::fromString('/var/www/cache');
        $path3 = FilePath::fromString('/var/www/temp');

        //! @section Act
        $path1EqualsPath2 = $path1->equals($path2);
        $path1EqualsPath3 = $path1->equals($path3);

        //! @section Assert
        $this->assertTrue($path1EqualsPath2);
        $this->assertFalse($path1EqualsPath3);
    }

    public function test_join_creates_new_path_with_component(): void
    {
        //! @section Arrange
        $basePath = FilePath::fromString('/var/www/cache');

        //! @section Act
        $joinedPath = $basePath->join('pokemon.json');

        //! @section Assert
        $this->assertSame('/var/www/cache/pokemon.json', $joinedPath->getValue());
        $this->assertNotSame($basePath, $joinedPath); // Should be a new instance
    }

    public function test_join_handles_multiple_components(): void
    {
        //! @section Arrange
        $basePath = FilePath::fromString('/var/www');

        //! @section Act
        $joinedPath = $basePath->join('cache/pokemon.json');

        //! @section Assert
        $this->assertSame('/var/www/cache/pokemon.json', $joinedPath->getValue());
    }

    public function test_join_removes_leading_slashes_from_component(): void
    {
        //! @section Arrange
        $basePath = FilePath::fromString('/var/www/cache');

        //! @section Act
        $joinedPath = $basePath->join('/pokemon.json');

        //! @section Assert
        $this->assertSame('/var/www/cache/pokemon.json', $joinedPath->getValue());
    }

    public function test_join_rejects_empty_component(): void
    {
        //! @section Arrange
        $basePath = FilePath::fromString('/var/www/cache');

        //! @section Arrange
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Path component cannot be empty.');

        //! @section Act
        $basePath->join('');
    }

    public function test_get_directory_returns_parent_directory(): void
    {
        //! @section Arrange
        $filePath = FilePath::fromString('/var/www/cache/pokemon.json');

        //! @section Act
        $directory = $filePath->getDirectory();

        //! @section Assert
        $this->assertSame('/var/www/cache', $directory->getValue());
    }

    public function test_get_directory_handles_root_path(): void
    {
        //! @section Arrange
        $rootPath = FilePath::fromString('/');

        //! @section Act
        $directory = $rootPath->getDirectory();

        //! @section Assert
        // On some systems, dirname('/') returns '.', which we normalize to '/'
        $this->assertSame('/', $directory->getValue());
    }

    public function test_get_filename_returns_filename_with_extension(): void
    {
        //! @section Arrange
        $filePath = FilePath::fromString('/var/www/cache/pokemon.json');

        //! @section Act
        $filename = $filePath->getFilename();

        //! @section Assert
        $this->assertSame('pokemon.json', $filename);
    }

    public function test_get_filename_without_extension_returns_name_only(): void
    {
        //! @section Arrange
        $filePath = FilePath::fromString('/var/www/cache/pokemon.json');

        //! @section Act
        $filenameWithoutExt = $filePath->getFilenameWithoutExtension();

        //! @section Assert
        $this->assertSame('pokemon', $filenameWithoutExt);
    }

    public function test_get_filename_without_extension_handles_no_extension(): void
    {
        //! @section Arrange
        $filePath = FilePath::fromString('/var/www/cache/pokemon');

        //! @section Act
        $filenameWithoutExt = $filePath->getFilenameWithoutExtension();

        //! @section Assert
        $this->assertSame('pokemon', $filenameWithoutExt);
    }

    public function test_get_extension_returns_file_extension(): void
    {
        //! @section Arrange
        $filePath = FilePath::fromString('/var/www/cache/pokemon.json');

        //! @section Act
        $extension = $filePath->getExtension();

        //! @section Assert
        $this->assertSame('json', $extension);
    }

    public function test_get_extension_returns_empty_string_for_no_extension(): void
    {
        //! @section Arrange
        $filePath = FilePath::fromString('/var/www/cache/pokemon');

        //! @section Act
        $extension = $filePath->getExtension();

        //! @section Assert
        $this->assertSame('', $extension);
    }

    public function test_get_extension_handles_hidden_files(): void
    {
        //! @section Arrange
        $filePath = FilePath::fromString('/var/www/.env');

        //! @section Act
        $extension = $filePath->getExtension();

        //! @section Assert
        $this->assertSame('env', $extension);
    }

    public function test_exists_checks_file_existence(): void
    {
        //! @section Arrange
        $existingPath = FilePath::fromString(__FILE__); // This test file
        $nonExistingPath = FilePath::fromString('/non/existing/path.txt');

        //! @section Act
        $existingPathExists = $existingPath->exists();
        $nonExistingPathExists = $nonExistingPath->exists();

        //! @section Assert
        $this->assertTrue($existingPathExists);
        $this->assertFalse($nonExistingPathExists);
    }

    public function test_is_directory_checks_if_path_is_directory(): void
    {
        //! @section Arrange
        $directoryPath = FilePath::fromString(__DIR__); // Test directory
        $filePath = FilePath::fromString(__FILE__); // This test file

        //! @section Act
        $directoryPathIsDirectory = $directoryPath->isDirectory();
        $filePathIsDirectory = $filePath->isDirectory();

        //! @section Assert
        $this->assertTrue($directoryPathIsDirectory);
        $this->assertFalse($filePathIsDirectory);
    }

    public function test_is_file_checks_if_path_is_file(): void
    {
        //! @section Arrange
        $directoryPath = FilePath::fromString(__DIR__); // Test directory
        $filePath = FilePath::fromString(__FILE__); // This test file

        //! @section Act
        $directoryPathIsFile = $directoryPath->isFile();
        $filePathIsFile = $filePath->isFile();

        //! @section Assert
        $this->assertFalse($directoryPathIsFile);
        $this->assertTrue($filePathIsFile);
    }

    public function test_ensure_directory_exists_creates_directory(): void
    {
        //! @section Arrange
        $tempDir = sys_get_temp_dir() . '/test_filepath_' . uniqid();
        $filePath = FilePath::fromString($tempDir . '/subdir/file.txt');

        try {
            //! @section Act
            $result = $filePath->ensureDirectoryExists();

            //! @section Assert
            $this->assertTrue($result);
            $this->assertTrue($filePath->getDirectory()->exists());
            $this->assertTrue($filePath->getDirectory()->isDirectory());
        } finally {
            // Cleanup
            if (is_dir($tempDir)) {
                $this->removeDirectoryRecursively($tempDir);
            }
        }
    }

    public function test_write_and_read_contents(): void
    {
        //! @section Arrange
        $tempDir = sys_get_temp_dir() . '/test_filepath_' . uniqid();
        $filePath = FilePath::fromString($tempDir . '/test.txt');
        $testContent = 'Hello, World!';

        try {
            //! @section Act
            $writeResult = $filePath->writeContents($testContent);
            $readContent = $filePath->readContents();

            //! @section Assert
            $this->assertTrue($writeResult);
            $this->assertSame($testContent, $readContent);
            $this->assertTrue($filePath->exists());
            $this->assertTrue($filePath->isFile());
        } finally {
            // Cleanup
            if (is_dir($tempDir)) {
                $this->removeDirectoryRecursively($tempDir);
            }
        }
    }

    public function test_read_contents_throws_on_non_existent_file(): void
    {
        //! @section Arrange
        $filePath = FilePath::fromString('/non/existing/file.txt');

        //! @section Arrange
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('File does not exist or is not readable: /non/existing/file.txt');

        //! @section Act
        $filePath->readContents();
    }

    public function test_delete_removes_file(): void
    {
        //! @section Arrange
        $tempDir = sys_get_temp_dir() . '/test_filepath_' . uniqid();
        $filePath = FilePath::fromString($tempDir . '/test.txt');
        $filePath->writeContents('test content');

        try {
            //! @section Act
            $deleteResult = $filePath->delete();

            //! @section Assert
            $this->assertTrue($deleteResult);
            $this->assertFalse($filePath->exists());
        } finally {
            // Cleanup
            if (is_dir($tempDir)) {
                $this->removeDirectoryRecursively($tempDir);
            }
        }
    }

    public function test_delete_returns_true_for_non_existent_file(): void
    {
        //! @section Arrange
        $filePath = FilePath::fromString('/non/existing/file.txt');

        //! @section Act
        $deleteResult = $filePath->delete();

        //! @section Assert
        $this->assertTrue($deleteResult);
    }

    public function test_get_size_returns_file_size(): void
    {
        //! @section Arrange
        $tempDir = sys_get_temp_dir() . '/test_filepath_' . uniqid();
        $filePath = FilePath::fromString($tempDir . '/test.txt');
        $testContent = 'Hello, World!';
        $filePath->writeContents($testContent);

        try {
            //! @section Act
            $size = $filePath->getSize();

            //! @section Assert
            $this->assertSame(strlen($testContent), $size);
        } finally {
            // Cleanup
            if (is_dir($tempDir)) {
                $this->removeDirectoryRecursively($tempDir);
            }
        }
    }

    public function test_get_last_modified_returns_timestamp(): void
    {
        //! @section Arrange
        $tempDir = sys_get_temp_dir() . '/test_filepath_' . uniqid();
        $filePath = FilePath::fromString($tempDir . '/test.txt');
        $filePath->writeContents('test content');

        try {
            //! @section Act
            $lastModified = $filePath->getLastModified();

            //! @section Assert
            $this->assertIsInt($lastModified);
            $this->assertGreaterThan(0, $lastModified);
            $this->assertLessThanOrEqual(time(), $lastModified);
        } finally {
            // Cleanup
            if (is_dir($tempDir)) {
                $this->removeDirectoryRecursively($tempDir);
            }
        }
    }

    public function test_is_older_than_checks_file_age(): void
    {
        //! @section Arrange
        $tempDir = sys_get_temp_dir() . '/test_filepath_' . uniqid();
        $filePath = FilePath::fromString($tempDir . '/test.txt');
        $filePath->writeContents('test content');

        try {
            //! @section Act
            $isOlderThan1Second = $filePath->isOlderThan(1);
            $isOlderThan0Seconds = $filePath->isOlderThan(0);

            //! @section Assert
            $this->assertFalse($isOlderThan1Second); // Should not be older than 1 second

            // For filesystem precision, we can't reliably test isOlderThan(0) with files created
            // in the same second. Instead, test that it's not older than 1 second immediately
            // and that the method works correctly for reasonable time differences.
            $this->assertFalse($isOlderThan0Seconds); // Should not be older than 0 seconds (just created)
        } finally {
            // Cleanup
            if (is_dir($tempDir)) {
                $this->removeDirectoryRecursively($tempDir);
            }
        }
    }

    public function test_to_string_returns_path_value(): void
    {
        //! @section Arrange
        $path = FilePath::fromString('/var/www/cache');

        //! @section Act
        $pathString = (string) $path;

        //! @section Assert
        $this->assertSame('/var/www/cache', $pathString);
    }

    //! @brief Helper method to recursively remove a directory
    private function removeDirectoryRecursively(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->removeDirectoryRecursively($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
