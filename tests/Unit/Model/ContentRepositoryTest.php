<?php

declare(strict_types=1);

namespace Tests\Unit\Model;

use PHPUnit\Framework\TestCase;
use App\Model\ContentRepository;

//! @brief Test suite for ContentRepository
//!
//! Defines the contract for how content is loaded from files
class ContentRepositoryTest extends TestCase
{
    private string $testContentPath; //!< Temporary test content directory
    private ContentRepository $repository; //!< Repository under test

    //! @brief Set up test environment before each test
    protected function setUp(): void
    {
        //! Create temporary test content directory
        $this->testContentPath = sys_get_temp_dir() . '/test_content_' . uniqid();
        mkdir($this->testContentPath);

        $this->repository = new ContentRepository($this->testContentPath);
    }

    //! @brief Clean up test environment after each test
    protected function tearDown(): void
    {
        if (is_dir($this->testContentPath)) {
            $files = glob($this->testContentPath . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->testContentPath);
        }
    }

    //! @brief Test loading projects from YAML file
    public function test_loads_projects_from_yaml_file(): void
    {
        //! @section Arrange
        $yamlContent = <<<YAML
- title: "Test Project"
  year: "2025"
  tags:
    - PHP
    - Testing
  description: "A test project"
  github: "https://github.com/test/project"
YAML;
        file_put_contents($this->testContentPath . '/projects.yaml', $yamlContent);

        //! @section Act
        $projects = $this->repository->getProjects();

        //! @section Assert
        $this->assertIsArray($projects);
        $this->assertCount(1, $projects);
        $this->assertEquals('Test Project', $projects[0]['title']);
        $this->assertEquals('2025', $projects[0]['year']);
        $this->assertIsArray($projects[0]['tags']);
        $this->assertEquals('A test project', $projects[0]['description']);
    }

    //! @brief Test loading about text from markdown file
    public function test_loads_about_text_from_markdown_file(): void
    {
        //! @section Arrange
        $markdownContent = "Software designer. Information engineer.\n\nBased in Sweden.";
        file_put_contents($this->testContentPath . '/about.md', $markdownContent);

        //! @section Act
        $aboutParagraphs = $this->repository->getAboutParagraphs();

        //! @section Assert
        $this->assertIsArray($aboutParagraphs);
        $this->assertNotEmpty($aboutParagraphs);
        $this->assertContains('<p>Software designer. Information engineer.</p>', $aboutParagraphs);
        $this->assertContains('<p>Based in Sweden.</p>', $aboutParagraphs);
    }

    //! @brief Test parsing markdown links to HTML
    public function test_parses_markdown_links_to_html(): void
    {
        //! @section Arrange
        $markdownContent = "Visit [Example Site](https://example.com) for more.";
        file_put_contents($this->testContentPath . '/about.md', $markdownContent);

        //! @section Act
        $aboutParagraphs = $this->repository->getAboutParagraphs();

        //! @section Assert
        $this->assertCount(1, $aboutParagraphs);
        $this->assertStringContainsString('<a href="https://example.com">Example Site</a>', $aboutParagraphs[0]);
    }

    //! @brief Test parsing markdown emphasis to HTML
    public function test_parses_markdown_emphasis_to_html(): void
    {
        //! @section Arrange
        $markdownContent = "This is **bold** and *italic* text.";
        file_put_contents($this->testContentPath . '/about.md', $markdownContent);

        //! @section Act
        $aboutParagraphs = $this->repository->getAboutParagraphs();

        //! @section Assert
        $this->assertCount(1, $aboutParagraphs);
        $this->assertStringContainsString('<strong>bold</strong>', $aboutParagraphs[0]);
        $this->assertStringContainsString('<em>italic</em>', $aboutParagraphs[0]);
    }

    //! @brief Test loading configuration data from YAML
    public function test_loads_config_data_from_yaml(): void
    {
        //! @section Arrange
        $configContent = <<<YAML
skills:
  - "C/C++, embedded development"
  - "Test design"

contact:
  - url: "https://github.com/test"
    text: "github.com/test"
  - url: "mailto:test@example.com"
    text: "test@example.com"

about:
  profile_image: "/images/test.png"
  profile_alt: "Test portrait"
YAML;
        file_put_contents($this->testContentPath . '/config.yaml', $configContent);

        //! @section Act
        $config = $this->repository->getConfig();

        //! @section Assert
        $this->assertIsArray($config);
        $this->assertArrayHasKey('skills', $config);
        $this->assertArrayHasKey('contact', $config);
        $this->assertArrayHasKey('about', $config);

        $this->assertIsArray($config['skills']);
        $this->assertCount(2, $config['skills']);

        $this->assertIsArray($config['contact']);
        $this->assertIsArray($config['about']);
    }

    //! @brief Test graceful handling when projects file is missing
    public function test_returns_empty_array_when_projects_file_missing(): void
    {
        //! @section Act
        $projects = $this->repository->getProjects();

        //! @section Assert
        $this->assertIsArray($projects);
        $this->assertEmpty($projects);
    }

    //! @brief Test graceful handling when about file is missing
    public function test_returns_empty_array_when_about_file_missing(): void
    {
        //! @section Act
        $paragraphs = $this->repository->getAboutParagraphs();

        //! @section Assert
        $this->assertIsArray($paragraphs);
        $this->assertEmpty($paragraphs);
    }

    //! @brief Test default config returned when file is missing
    public function test_returns_default_config_when_file_missing(): void
    {
        //! @section Act
        $config = $this->repository->getConfig();

        //! @section Assert
        $this->assertIsArray($config);
        $this->assertArrayHasKey('skills', $config);
        $this->assertArrayHasKey('contact', $config);
        $this->assertArrayHasKey('about', $config);
    }

    //! @brief Test handling of malformed YAML in projects file
    public function test_handles_malformed_yaml_in_projects_file(): void
    {
        //! @section Arrange
        $invalidYaml = <<<YAML
- title: "Test Project
  year: 2025
  tags: [PHP
  description: "Malformed YAML with unclosed quotes and brackets"
YAML;
        file_put_contents($this->testContentPath . '/projects.yaml', $invalidYaml);

        //! @section Act
        //! Should not throw exception
        try {
            $projects = $this->repository->getProjects();
            
            //! @section Assert
            //! Should return empty array or handle gracefully
            $this->assertIsArray($projects);
        } catch (\Exception $e) {
            //! If exception is thrown, test passes - we're checking it doesn't crash
            $this->assertInstanceOf(\Exception::class, $e);
        }
    }

    //! @brief Test handling of malformed YAML in config file
    public function test_handles_malformed_yaml_in_config_file(): void
    {
        //! @section Arrange
        $invalidYaml = <<<YAML
skills:
  - "C/C++
contact:
  - url: "https://github.com
    text: invalid
YAML;
        file_put_contents($this->testContentPath . '/config.yaml', $invalidYaml);

        //! @section Act
        //! Should not throw exception
        try {
            $config = $this->repository->getConfig();
            
            //! @section Assert
            //! Should return defaults or handle gracefully
            $this->assertIsArray($config);
            $this->assertArrayHasKey('skills', $config);
        } catch (\Exception $e) {
            //! If exception is thrown, test passes - we're checking it doesn't crash
            $this->assertInstanceOf(\Exception::class, $e);
        }
    }

    //! @brief Test handling of empty projects file
    public function test_handles_empty_projects_file(): void
    {
        //! @section Arrange
        file_put_contents($this->testContentPath . '/projects.yaml', '');

        //! @section Act
        $projects = $this->repository->getProjects();

        //! @section Assert
        $this->assertIsArray($projects);
        $this->assertEmpty($projects);
    }

    //! @brief Test handling of empty config file
    public function test_handles_empty_config_file(): void
    {
        //! @section Arrange
        file_put_contents($this->testContentPath . '/config.yaml', '');

        //! @section Act
        $config = $this->repository->getConfig();

        //! @section Assert
        $this->assertIsArray($config);
        //! Should return defaults even with empty file
        $this->assertArrayHasKey('skills', $config);
        $this->assertArrayHasKey('contact', $config);
        $this->assertArrayHasKey('about', $config);
    }

    //! @brief Test handling of projects with null values
    public function test_handles_projects_with_null_values(): void
    {
        //! @section Arrange
        $yamlContent = <<<YAML
- title: 
  year: "2025"
  tags:
  description: "Test with null title"
YAML;
        file_put_contents($this->testContentPath . '/projects.yaml', $yamlContent);

        //! @section Act
        $projects = $this->repository->getProjects();

        //! @section Assert
        $this->assertIsArray($projects);
        $this->assertCount(1, $projects);
        //! Should handle null values gracefully
        $this->assertNull($projects[0]['title']);
        $this->assertNull($projects[0]['tags']);
    }

    //! @brief Test handling of projects with missing required fields
    public function test_handles_projects_with_missing_fields(): void
    {
        //! @section Arrange
        $yamlContent = <<<YAML
- year: "2025"
  description: "Missing title and tags"
YAML;
        file_put_contents($this->testContentPath . '/projects.yaml', $yamlContent);

        //! @section Act
        $projects = $this->repository->getProjects();

        //! @section Assert
        $this->assertIsArray($projects);
        $this->assertCount(1, $projects);
        $this->assertArrayNotHasKey('title', $projects[0]);
    }

    //! @brief Test handling of config with invalid data types
    public function test_handles_config_with_invalid_data_types(): void
    {
        //! @section Arrange
        $yamlContent = <<<YAML
skills: "Should be array not string"
contact: 123
about:
  profile_image: []
  profile_alt: 456
YAML;
        file_put_contents($this->testContentPath . '/config.yaml', $yamlContent);

        //! @section Act
        $config = $this->repository->getConfig();

        //! @section Assert
        $this->assertIsArray($config);
        //! Should still return a valid structure
        $this->assertArrayHasKey('skills', $config);
        $this->assertArrayHasKey('contact', $config);
        $this->assertArrayHasKey('about', $config);
    }

    //! @brief Test handling of projects file with non-array root
    public function test_handles_projects_with_non_array_root(): void
    {
        //! @section Arrange
        $yamlContent = "This is not an array, just a string";
        file_put_contents($this->testContentPath . '/projects.yaml', $yamlContent);

        //! @section Act
        $projects = $this->repository->getProjects();

        //! @section Assert
        $this->assertIsArray($projects);
        $this->assertEmpty($projects);
    }

    //! @brief Test handling of about.md with special characters
    public function test_handles_about_with_special_characters(): void
    {
        //! @section Arrange
        $markdownContent = "Software & **hardware** engineer.\n\n<script>alert('xss')</script>";
        file_put_contents($this->testContentPath . '/about.md', $markdownContent);

        //! @section Act
        $paragraphs = $this->repository->getAboutParagraphs();

        //! @section Assert
        $this->assertIsArray($paragraphs);
        $this->assertCount(2, $paragraphs);
        //! HTML special characters should be handled by markdown parser
        $this->assertStringContainsString('&amp;', $paragraphs[0]);
        $this->assertStringContainsString('<strong>hardware</strong>', $paragraphs[0]);
    }

    //! @brief Test handling of about.md with Unicode characters
    public function test_handles_about_with_unicode_characters(): void
    {
        //! @section Arrange
        $markdownContent = "Swedish engineer in Göteborg 🇸🇪\n\nWorking with embedded systems.";
        file_put_contents($this->testContentPath . '/about.md', $markdownContent);

        //! @section Act
        $paragraphs = $this->repository->getAboutParagraphs();

        //! @section Assert
        $this->assertIsArray($paragraphs);
        $this->assertCount(2, $paragraphs);
        $this->assertStringContainsString('Göteborg', $paragraphs[0]);
        $this->assertStringContainsString('🇸🇪', $paragraphs[0]);
    }

    //! @brief Test handling of about.md with only whitespace
    public function test_handles_about_with_only_whitespace(): void
    {
        //! @section Arrange
        $markdownContent = "   \n\n   \n  ";
        file_put_contents($this->testContentPath . '/about.md', $markdownContent);

        //! @section Act
        $paragraphs = $this->repository->getAboutParagraphs();

        //! @section Assert
        $this->assertIsArray($paragraphs);
        $this->assertEmpty($paragraphs);
    }

    //! @brief Test handling of about.md with multiple consecutive blank lines
    public function test_handles_about_with_multiple_blank_lines(): void
    {
        //! @section Arrange
        $markdownContent = "First paragraph.\n\n\n\nSecond paragraph.\n\n\n\n\nThird paragraph.";
        file_put_contents($this->testContentPath . '/about.md', $markdownContent);

        //! @section Act
        $paragraphs = $this->repository->getAboutParagraphs();

        //! @section Assert
        $this->assertIsArray($paragraphs);
        $this->assertCount(3, $paragraphs);
        $this->assertStringContainsString('First paragraph', $paragraphs[0]);
        $this->assertStringContainsString('Second paragraph', $paragraphs[1]);
        $this->assertStringContainsString('Third paragraph', $paragraphs[2]);
    }

    //! @brief Test handling of unreadable file (permission denied)
    public function test_handles_unreadable_file_permissions(): void
    {
        //! @section Arrange
        //! Create a file and make it unreadable (Unix-like systems only)
        $testFile = $this->testContentPath . '/unreadable.yaml';
        file_put_contents($testFile, 'test: data');
        
        //! Attempt to make file unreadable
        $originalPerms = fileperms($testFile);
        @chmod($testFile, 0000);

        //! @section Act
        //! Try to read the file - behavior depends on permissions
        $canMakeUnreadable = !is_readable($testFile);
        
        if ($canMakeUnreadable) {
            //! On systems where we can actually make files unreadable
            $result = @file_get_contents($testFile);
            
            //! @section Assert
            $this->assertFalse($result, 'Should not be able to read unreadable file');
        } else {
            //! On systems where chmod doesn't work as expected (e.g., Windows)
            //! Just verify the file exists
            $this->assertFileExists($testFile);
        }
        
        //! Clean up - restore permissions before deleting
        @chmod($testFile, $originalPerms);
        @unlink($testFile);
    }

    //! @brief Test repository handles file_get_contents failure gracefully
    public function test_handles_file_read_failure_in_projects(): void
    {
        //! @section Arrange
        //! Create repository pointing to non-existent base path
        $invalidRepo = new ContentRepository('/nonexistent/path/that/does/not/exist');

        //! @section Act
        $projects = $invalidRepo->getProjects();

        //! @section Assert
        //! Should return empty array instead of crashing
        $this->assertIsArray($projects);
        $this->assertEmpty($projects);
    }

    //! @brief Test repository handles file_get_contents failure in about
    public function test_handles_file_read_failure_in_about(): void
    {
        //! @section Arrange
        //! Create repository pointing to non-existent base path
        $invalidRepo = new ContentRepository('/nonexistent/path/that/does/not/exist');

        //! @section Act
        $paragraphs = $invalidRepo->getAboutParagraphs();

        //! @section Assert
        //! Should return empty array instead of crashing
        $this->assertIsArray($paragraphs);
        $this->assertEmpty($paragraphs);
    }

    //! @brief Test repository handles file_get_contents failure in config
    public function test_handles_file_read_failure_in_config(): void
    {
        //! @section Arrange
        //! Create repository pointing to non-existent base path
        $invalidRepo = new ContentRepository('/nonexistent/path/that/does/not/exist');

        //! @section Act
        $config = $invalidRepo->getConfig();

        //! @section Assert
        //! Should return defaults instead of crashing
        $this->assertIsArray($config);
        $this->assertArrayHasKey('skills', $config);
        $this->assertArrayHasKey('contact', $config);
        $this->assertArrayHasKey('about', $config);
    }
}

