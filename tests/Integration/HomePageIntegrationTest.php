<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Model\ContentRepository;
use App\Presenter\HomePresenter;

//! @brief Integration test for the complete MVP flow
//!
//! Tests that ContentRepository -> HomePresenter produces correct output
class HomePageIntegrationTest extends TestCase
{
    private string $testContentPath; //!< Temporary test content directory
    private ContentRepository $repository; //!< Repository under test
    private HomePresenter $presenter; //!< Presenter under test

    //! @brief Set up test environment before each test
    protected function setUp(): void
    {
        $this->testContentPath = sys_get_temp_dir() . '/test_integration_' . uniqid();
        mkdir($this->testContentPath);

        $this->createTestContentFiles();

        $this->repository = new ContentRepository($this->testContentPath);
        $this->presenter = new HomePresenter($this->repository);
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

    //! @brief Create test content files in temporary directory
    private function createTestContentFiles(): void
    {
        //! Create about.md
        $aboutContent = "Software designer and developer.\n\nBased in Sweden.";
        file_put_contents($this->testContentPath . '/about.md', $aboutContent);

        //! Create projects.yaml
        $projectsContent = <<<YAML
- title: "Test Project"
  year: "2025"
  tags:
    - PHP
    - Testing
  description: "A test project for integration testing."
  github: "https://github.com/test/project"
YAML;
        file_put_contents($this->testContentPath . '/projects.yaml', $projectsContent);

        //! Create config.yaml
        $configContent = <<<YAML
about:
  profile_image: "/images/test.png"
  profile_alt: "Test portrait"

skills:
  - "PHP Development"
  - "Test-Driven Development"

contact:
  - url: "https://github.com/test"
    text: "github.com/test"
  - url: "mailto:test@example.com"
    text: "test@example.com"
YAML;
        file_put_contents($this->testContentPath . '/config.yaml', $configContent);
    }

    //! @brief Test complete MVP flow produces valid data
    public function test_complete_mvp_flow_produces_valid_data(): void
    {
        //! @section Act
        $data = $this->presenter->present();

        //! @section Assert
        //! Check all top-level keys
        $this->assertArrayHasKey('about', $data);
        $this->assertArrayHasKey('skills', $data);
        $this->assertArrayHasKey('projects', $data);
        $this->assertArrayHasKey('contact', $data);

        //! About section
        $this->assertEquals('/images/test.png', $data['about']['profile_image']);
        $this->assertEquals('Test portrait', $data['about']['profile_alt']);
        $this->assertCount(2, $data['about']['paragraphs']);
        $this->assertEquals('Software designer and developer.', $data['about']['paragraphs'][0]);
        $this->assertEquals('Based in Sweden.', $data['about']['paragraphs'][1]);

        //! Skills
        $this->assertCount(2, $data['skills']);
        $this->assertEquals('PHP Development', $data['skills'][0]);

        //! Projects
        $this->assertCount(1, $data['projects']);
        $this->assertEquals('Test Project', $data['projects'][0]['title']);
        $this->assertEquals('2025', $data['projects'][0]['year']);
        $this->assertIsArray($data['projects'][0]['tags']);
        $this->assertContains('PHP', $data['projects'][0]['tags']);

        //! Contact
        $this->assertCount(2, $data['contact']['links']);
        $this->assertEquals('https://github.com/test', $data['contact']['links'][0]['url']);
        $this->assertEquals('github.com/test', $data['contact']['links'][0]['text']);
    }

    //! @brief Test graceful handling of missing content files
    public function test_handles_missing_content_files_gracefully(): void
    {
        //! @section Arrange
        //! Create repository with empty directory
        $emptyPath = sys_get_temp_dir() . '/test_empty_' . uniqid();
        mkdir($emptyPath);
        $emptyRepository = new ContentRepository($emptyPath);
        $emptyPresenter = new HomePresenter($emptyRepository);

        //! @section Act
        $data = $emptyPresenter->present();

        //! @section Assert
        //! Should return default structure
        $this->assertArrayHasKey('about', $data);
        $this->assertArrayHasKey('skills', $data);
        $this->assertArrayHasKey('projects', $data);
        $this->assertArrayHasKey('contact', $data);

        $this->assertEmpty($data['about']['paragraphs']);
        $this->assertEmpty($data['skills']);
        $this->assertEmpty($data['projects']);

        //! Cleanup
        rmdir($emptyPath);
    }
}

