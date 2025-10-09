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
        $this->assertContains('Software designer. Information engineer.', $aboutParagraphs);
        $this->assertContains('Based in Sweden.', $aboutParagraphs);
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
}

