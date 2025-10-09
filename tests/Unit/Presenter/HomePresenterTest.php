<?php

declare(strict_types=1);

namespace Tests\Unit\Presenter;

use PHPUnit\Framework\TestCase;
use App\Presenter\HomePresenter;

//! @brief Test suite for HomePresenter
//!
//! Defines the contract for what data structure the home view expects
class HomePresenterTest extends TestCase
{
    private HomePresenter $presenter; //!< Presenter under test

    //! @brief Set up test environment before each test
    protected function setUp(): void
    {
        $this->presenter = new HomePresenter();
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
}

