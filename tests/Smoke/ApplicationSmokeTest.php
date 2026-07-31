<?php

declare(strict_types=1);

namespace Tests\Smoke;

use PHPUnit\Framework\TestCase;
use App\Site\Home\ContentRepository;
use App\Shared\Support\FilePath;
use App\Site\Home\HomePresenter;

//! @brief Smoke tests to verify critical application paths work
//!
//! These tests should be FAST and verify only the most critical functionality.
//! If these fail, there's no point running more comprehensive tests.
class ApplicationSmokeTest extends TestCase
{
    //! @brief Test that PSR-4 autoloader is working
    public function test_autoloader_works(): void
    {
        //! @section Assert
        $this->assertTrue(class_exists(\PHPUnit\Framework\TestCase::class));
        $this->assertTrue(class_exists(ContentRepository::class));
        $this->assertTrue(class_exists(HomePresenter::class));
    }

    //! @brief Test that Twig templating dependency loads
    public function test_twig_dependency_loads(): void
    {
        //! @section Assert
        $this->assertTrue(class_exists(\Twig\Environment::class));
    }

    //! @brief Test that YAML parser dependency loads
    public function test_yaml_dependency_loads(): void
    {
        //! @section Assert
        $this->assertTrue(class_exists(\Symfony\Component\Yaml\Yaml::class));
    }

    //! @brief Test that ContentRepository can be instantiated
    public function test_can_instantiate_content_repository(): void
    {
        //! @section Act
        $repository = new ContentRepository(FilePath::fromString((string)realpath(__DIR__ . '/../../content')));

        //! @section Assert
        $this->assertInstanceOf(ContentRepository::class, $repository);
    }

    //! @brief Test that HomePresenter can be instantiated and called
    public function test_can_instantiate_and_call_home_presenter(): void
    {
        //! @section Arrange
        $repository = new ContentRepository(FilePath::fromString((string)realpath(__DIR__ . '/../../content')));
        $presenter = new HomePresenter($repository);

        //! @section Act
        $data = $presenter->present();

        //! @section Assert
        $this->assertIsArray($data);
        $this->assertArrayHasKey('about', $data);
        $this->assertArrayHasKey('skills', $data);
        $this->assertArrayHasKey('projects', $data);
        $this->assertArrayHasKey('contact', $data);
    }
}

