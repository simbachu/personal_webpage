<?php

declare(strict_types=1);

namespace App\Model;

use App\Type\FilePath;

use Symfony\Component\Yaml\Yaml;
use League\CommonMark\CommonMarkConverter;

//! @brief ContentRepository - Loads content from YAML and Markdown files
//!
//! This repository handles file I/O and parsing of content files,
//! providing structured data to the presenter layer.
class ContentRepository
{
    private FilePath $contentPath; //!< Path to content directory

    //! @brief Constructor
    //! @param contentPath Path to content directory
    public function __construct(string $contentPath)
    {
        $this->contentPath = FilePath::fromString($contentPath);
    }

    //! @brief Load projects from projects.yaml
    //!
    //! @retval array<array{
    //!     title: string,
    //!     year: string,
    //!     tags: string[],
    //!     description: string,
    //!     github?: string,
    //!     award?: string
    //! }> Array of project data structures
    public function getProjects(): array
    {
        $filePath = $this->contentPath->join('projects.yaml');

        if (!$filePath->exists()) {
            return [];
        }

        try {
            $content = $filePath->readContents();
        } catch (\RuntimeException $e) {
            return [];
        }

        $projects = Yaml::parse($content);

        return is_array($projects) ? $projects : [];
    }

    //! @brief Load about section paragraphs from about.md
    //!
    //! Splits markdown content by double newlines into paragraphs and parses each to HTML
    //!
    //! @retval string[] Array of HTML paragraph strings
    public function getAboutParagraphs(): array
    {
        $filePath = $this->contentPath->join('about.md');

        if (!$filePath->exists()) {
            return [];
        }

        try {
            $content = $filePath->readContents();
        } catch (\RuntimeException $e) {
            return [];
        }

        //! Split by double newlines (paragraph breaks)
        $paragraphs = preg_split('/\n\s*\n/', trim($content));

        if ($paragraphs === false) {
            return [];
        }

        //! Filter out empty paragraphs and trim whitespace
        $paragraphs = array_values(array_filter(array_map('trim', $paragraphs)));

        //! Parse each paragraph from Markdown to HTML
        $converter = new CommonMarkConverter();
        $htmlParagraphs = [];

        foreach ($paragraphs as $paragraph) {
            $html = $converter->convert($paragraph)->getContent();
            //! Remove trailing newline that CommonMark adds
            $htmlParagraphs[] = rtrim($html);
        }

        return $htmlParagraphs;
    }

    //! @brief Load configuration from config.yaml
    //!
    //! @retval array{
    //!     skills: string[],
    //!     contact: array<array{url: string, text: string}>,
    //!     about: array{profile_image: string, profile_alt: string}
    //! } Configuration array with skills, contact, and about metadata
    public function getConfig(): array
    {
        $filePath = $this->contentPath->join('config.yaml');

        $defaults = [
            'skills' => [],
            'contact' => [],
            'about' => [
                'profile_image' => '/images/jg_devops_halftone.png',
                'profile_alt' => 'Jennifer Gott portrait',
            ],
        ];

        if (!$filePath->exists()) {
            return $defaults;
        }

        try {
            $content = $filePath->readContents();
        } catch (\RuntimeException $e) {
            return $defaults;
        }

        $config = Yaml::parse($content);

        if (!is_array($config)) {
            return $defaults;
        }

        //! Merge with defaults to ensure all required keys exist
        return array_merge($defaults, $config);
    }
}

