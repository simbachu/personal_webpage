<?php

declare(strict_types=1);

namespace App\Model;

//! @brief Value object representing an article
//! To be consumed by the Article template
final class Article
{
    public function __construct(
        public readonly string $slug,
        public readonly string $title,
        public readonly ?string $author,
        public readonly ?string $date,
        public readonly string $content,
        public readonly array $tags,
        public readonly bool $published,
        public readonly ?string $description,
        public readonly array $footnotes = []
    ) {}

    //! @brief Check if the article is published
    //! @retval bool True if the article is published, false otherwise
    public function isPublished(): bool
    {
        return $this->published;
    }

    //!@brief Get the article's file path (for file-based repositories)
    //! @retval string The article's file path
    public function getFilePath(): string
    {
        return $this->slug . '.md';
    }

    //!@brief Get a short description for meta tags
    //! @retval string The article's meta description
    public function getMetaDescription(): string
    {
        if ($this->description) {
            return $this->description;
        }

        if ($this->author) {
            return "Article by {$this->author} - {$this->title}";
        }

        return $this->title;
    }

    //!@brief Get the article's tags as a comma-separated string
    //! @retval string The article's tags as a comma-separated string
    public function getTagsAsString(): string
    {
        return implode(', ', $this->tags);
    }
}
