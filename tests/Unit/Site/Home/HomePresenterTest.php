<?php

declare(strict_types=1);

namespace Tests\Unit\Presenter;

use PHPUnit\Framework\TestCase;
use App\Presenter\HomePresenter;
use App\Model\ContentRepository;

//! @brief Test suite for HomePresenter
//!
//! Defines the contract for what data structure the home view expects
class HomePresenterTest extends TestCase
{
    private HomePresenter $presenter; //!< Presenter under test
    private ContentRepository $mockRepository; //!< Mocked repository

    //! @brief Set up test environment before each test
    protected function setUp(): void
    {
        //! Create mock repository with test data
        $this->mockRepository = $this->createMock(ContentRepository::class);
        
        //! Configure mock to return test data
        $this->mockRepository->method('getConfig')->willReturn([
            'skills' => ['PHP Development', 'Test-Driven Development'],
            'contact' => [
                ['url' => 'https://github.com/test', 'text' => 'github.com/test'],
                ['url' => 'mailto:test@example.com', 'text' => 'test@example.com'],
            ],
            'about' => [
                'profile_image' => '/images/test.png',
                'profile_alt' => 'Test portrait',
            ],
        ]);
        
        $this->mockRepository->method('getAboutParagraphs')->willReturn([
            '<p>Software designer. Information engineer.</p>',
            '<p>Based in Sweden.</p>',
        ]);
        
        $this->mockRepository->method('getProjects')->willReturn([
            [
                'title' => 'Test Project',
                'year' => '2025',
                'tags' => ['PHP', 'Testing'],
                'description' => 'A test project',
                'github' => 'https://github.com/test/project',
            ],
        ]);
        
        $this->presenter = new HomePresenter($this->mockRepository);
    }

    //! @brief Calling the presenter returns an array with the required fields
    public function test_returns_data_structure_with_all_required_keys(): void
    {
        //! @section Act
        $data = $this->presenter->present();

        //! @section Assert
        $this->assertIsArray($data);
        $this->assertArrayHasKey('about', $data);
        $this->assertArrayHasKey('skills', $data);
        $this->assertArrayHasKey('projects', $data);
        $this->assertArrayHasKey('contact', $data);
    }

    //! @brief Calling the presenter returns an array with the required fields
    public function test_about_section_contains_required_fields(): void
    {
        //! @section Act
        $data = $this->presenter->present();
        $about = $data['about'];

        //! @section Assert
        $this->assertIsArray($about);
        $this->assertArrayHasKey('profile_image', $about);
        $this->assertArrayHasKey('profile_alt', $about);
        $this->assertArrayHasKey('paragraphs', $about);

        $this->assertIsArray($about['paragraphs']);
        $this->assertNotEmpty($about['paragraphs']);

        foreach ($about['paragraphs'] as $paragraph) {
            $this->assertIsString($paragraph);
        }
    }

    //! @brief Calling the presenter returns an array with the required fields
    public function test_skills_is_array_of_strings(): void
    {
        //! @section Act
        $data = $this->presenter->present();
        $skills = $data['skills'];

        //! @section Assert
        $this->assertIsArray($skills);
        $this->assertNotEmpty($skills);

        foreach ($skills as $skill) {
            $this->assertIsString($skill);
        }
    }

    //! @brief Calling the presenter returns an array with the required fields
    public function test_projects_is_array_of_structured_data(): void
    {
        //! @section Act
        $data = $this->presenter->present();
        $projects = $data['projects'];

        //! @section Assert
        $this->assertIsArray($projects);
        $this->assertNotEmpty($projects);

        foreach ($projects as $project) {
            $this->assertIsArray($project);
            $this->assertArrayHasKey('title', $project);
            $this->assertArrayHasKey('year', $project);
            $this->assertArrayHasKey('tags', $project);
            $this->assertArrayHasKey('description', $project);

            $this->assertIsString($project['title']);
            $this->assertIsString($project['year']);
            $this->assertIsArray($project['tags']);
            $this->assertIsString($project['description']);

            //! Optional fields
            if (isset($project['github'])) {
                $this->assertIsString($project['github']);
            }
            if (isset($project['award'])) {
                $this->assertIsString($project['award']);
            }
        }
    }

    //! @brief Calling the presenter returns an array with the required fields
    public function test_contact_contains_structured_links(): void
    {
        //! @section Act
        $data = $this->presenter->present();
        $contact = $data['contact'];

        //! @section Assert
        $this->assertIsArray($contact);
        $this->assertArrayHasKey('links', $contact);
        $this->assertIsArray($contact['links']);
        $this->assertNotEmpty($contact['links']);

        foreach ($contact['links'] as $link) {
            $this->assertIsArray($link);
            $this->assertArrayHasKey('url', $link);
            $this->assertArrayHasKey('text', $link);
            $this->assertIsString($link['url']);
            $this->assertIsString($link['text']);
        }
    }

    //! @brief Test that presenter calls repository methods correctly
    public function test_calls_repository_methods(): void
    {
        //! @section Arrange
        $mockRepo = $this->createMock(ContentRepository::class);
        
        //! @section Assert
        //! Verify getConfig is called at least once
        $mockRepo->expects($this->atLeastOnce())
            ->method('getConfig')
            ->willReturn([
                'skills' => [],
                'contact' => [],
                'about' => ['profile_image' => '', 'profile_alt' => ''],
            ]);
        
        //! Verify getAboutParagraphs is called once
        $mockRepo->expects($this->once())
            ->method('getAboutParagraphs')
            ->willReturn([]);
        
        //! Verify getProjects is called once
        $mockRepo->expects($this->once())
            ->method('getProjects')
            ->willReturn([]);
        
        $presenter = new HomePresenter($mockRepo);
        
        //! @section Act
        $presenter->present();
    }

    //! @brief Test presenter correctly transforms config data
    public function test_transforms_config_data_correctly(): void
    {
        //! @section Act
        $data = $this->presenter->present();

        //! @section Assert
        //! Skills should be passed through directly
        $this->assertEquals(['PHP Development', 'Test-Driven Development'], $data['skills']);
        
        //! About section should include config data plus paragraphs
        $this->assertEquals('/images/test.png', $data['about']['profile_image']);
        $this->assertEquals('Test portrait', $data['about']['profile_alt']);
        
        //! Contact should be wrapped in 'links' key
        $this->assertCount(2, $data['contact']['links']);
        $this->assertEquals('https://github.com/test', $data['contact']['links'][0]['url']);
    }

    //! @brief Test presenter handles empty repository data gracefully
    public function test_handles_empty_repository_data(): void
    {
        //! @section Arrange
        $emptyMockRepo = $this->createMock(ContentRepository::class);
        $emptyMockRepo->method('getConfig')->willReturn([
            'skills' => [],
            'contact' => [],
            'about' => ['profile_image' => '', 'profile_alt' => ''],
        ]);
        $emptyMockRepo->method('getAboutParagraphs')->willReturn([]);
        $emptyMockRepo->method('getProjects')->willReturn([]);
        
        $presenter = new HomePresenter($emptyMockRepo);

        //! @section Act
        $data = $presenter->present();

        //! @section Assert
        $this->assertIsArray($data);
        $this->assertEmpty($data['skills']);
        $this->assertEmpty($data['projects']);
        $this->assertEmpty($data['about']['paragraphs']);
        $this->assertEmpty($data['contact']['links']);
    }

    //! @brief Test presenter with null repository uses stub data
    public function test_uses_stub_data_when_repository_is_null(): void
    {
        //! @section Arrange
        $presenter = new HomePresenter(null);

        //! @section Act
        $data = $presenter->present();

        //! @section Assert
        $this->assertIsArray($data);
        $this->assertArrayHasKey('about', $data);
        $this->assertArrayHasKey('skills', $data);
        $this->assertArrayHasKey('projects', $data);
        $this->assertArrayHasKey('contact', $data);
        
        //! Stub data should have content
        $this->assertNotEmpty($data['about']['paragraphs']);
        $this->assertNotEmpty($data['skills']);
        $this->assertNotEmpty($data['projects']);
    }

    //! @brief Test projects data structure is passed through correctly
    public function test_passes_through_projects_correctly(): void
    {
        //! @section Act
        $data = $this->presenter->present();
        $projects = $data['projects'];

        //! @section Assert
        $this->assertCount(1, $projects);
        $this->assertEquals('Test Project', $projects[0]['title']);
        $this->assertEquals('2025', $projects[0]['year']);
        $this->assertContains('PHP', $projects[0]['tags']);
        $this->assertEquals('A test project', $projects[0]['description']);
        $this->assertEquals('https://github.com/test/project', $projects[0]['github']);
    }
}

