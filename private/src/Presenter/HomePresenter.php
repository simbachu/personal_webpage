<?php

declare(strict_types=1);

namespace App\Presenter;

use App\Model\ContentRepository;

//! @brief HomePresenter - Prepares data for the home page view
//!
//! Transforms raw content data into a structure that the home template expects.
//! This presenter acts as the intermediary between the model layer and the view.
class HomePresenter
{
    private ?ContentRepository $repository; //!< Content repository instance

    //! @brief Constructor
    //! @param repository Content repository (null for stub data mode)
    public function __construct(?ContentRepository $repository = null)
    {
        $this->repository = $repository;
    }

    //! @brief Present data for the home page view
    //!
    //! @return array{
    //!     about: array{
    //!         profile_image: string,
    //!         profile_alt: string,
    //!         paragraphs: string[]
    //!     },
    //!     skills: string[],
    //!     projects: array<array{
    //!         title: string,
    //!         year: string,
    //!         tags: string[],
    //!         description: string,
    //!         github?: string,
    //!         award?: string
    //!     }>,
    //!     contact: array{
    //!         links: array<array{url: string, text: string}>
    //!     }
    //! } Complete view data structure
    public function present(): array
    {
        //! Use stub data if no repository provided (for backwards compatibility with tests)
        if ($this->repository === null) {
            return $this->presentStubData();
        }

        $config = $this->repository->getConfig();

        return [
            'about' => $this->presentAboutSection($config),
            'skills' => $this->presentSkills($config),
            'projects' => $this->presentProjects(),
            'contact' => $this->presentContact($config),
        ];
    }

    //! @brief Present about section data
    //! @param config Configuration array from repository
    //! @return array About section view data
    private function presentAboutSection(array $config): array
    {
        return [
            'profile_image' => $config['about']['profile_image'],
            'profile_alt' => $config['about']['profile_alt'],
            'paragraphs' => $this->repository->getAboutParagraphs(),
        ];
    }

    //! @brief Present skills list
    //! @param config Configuration array from repository
    //! @return array Skills array
    private function presentSkills(array $config): array
    {
        return $config['skills'];
    }

    //! @brief Present projects list
    //! @return array Projects array
    private function presentProjects(): array
    {
        return $this->repository->getProjects();
    }

    //! @brief Present contact information
    //! @param config Configuration array from repository
    //! @return array Contact data structure
    private function presentContact(array $config): array
    {
        return [
            'links' => $config['contact'],
        ];
    }

    //! @brief Provide stub data for testing without repository
    //! @return array Complete stub data structure
    private function presentStubData(): array
    {
        return [
            'about' => [
                'profile_image' => '/images/jg_devops_halftone.png',
                'profile_alt' => 'Jennifer Gott portrait',
                'paragraphs' => [
                    'Software designer. Information engineer. Illustrator.',
                    'Based in Gothenburg, Sweden.',
                ],
            ],
            'skills' => [
                '<strong>C/C++</strong>, embedded and native development',
                'Test design and program correctness',
            ],
            'projects' => [
                [
                    'title' => 'Conference room occupancy tracker',
                    'year' => '2025',
                    'tags' => ['Chas Academy', 'Arduino', 'C++', 'REST API', 'electronics'],
                    'description' => 'Room booking system with real-time occupancy information.',
                    'github' => 'https://github.com/Kusten-ar-klar-Chas-Challenge-2025/',
                    'award' => 'Nominated for Best Embedded Project',
                ],
            ],
            'contact' => [
                'links' => [
                    [
                        'url' => 'https://github.com/simbachu',
                        'text' => 'github.com/simbachu',
                    ],
                    [
                        'url' => 'mailto:simbachu@gmail.com',
                        'text' => 'simbachu@gmail.com',
                    ],
                ],
            ],
        ];
    }
}

