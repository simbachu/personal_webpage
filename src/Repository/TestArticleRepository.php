<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\Article;

//! @brief In-memory article repository for testing
final class TestArticleRepository implements ArticleRepository
{
    //! @var Article[] Array of articles in the repository
    private array $articles = [];

    //! @brief Add an article to the repository
    //! @param article The article to add
    public function addArticle(Article $article): void
    {
        $this->articles[$article->slug] = $article;
    }

    //! @brief Remove an article from the repository
    //! @param slug The slug of the article to remove
    public function removeArticle(string $slug): void
    {
        unset($this->articles[$slug]);
    }

    //! @brief Clear all articles from the repository
    public function clear(): void
    {
        $this->articles = [];
    }

    //! @brief Find all published articles
    //! @retval Article[] Array of published articles
    public function findPublished(): array
    {
        return array_filter($this->articles, fn(Article $article) => $article->isPublished());
    }

    //! @brief Find an article by its slug
    //! @param slug The article slug
    //! @retval Article|null The article if found, null otherwise
    public function findBySlug(string $slug): ?Article
    {
        return $this->articles[$slug] ?? null;
    }

    //! @brief Check if an article exists
    //! @param slug The article slug
    //! @retval bool True if the article exists, false otherwise
    public function exists(string $slug): bool
    {
        return isset($this->articles[$slug]);
    }

    //! @brief Get all articles (published and unpublished)
    //! @retval Article[] Array of all articles
    public function findAll(): array
    {
        return array_values($this->articles);
    }

    //! @brief Get the number of articles in the repository
    //! @retval int The number of articles
    public function count(): int
    {
        return count($this->articles);
    }

    //! @brief Get all article slugs
    //! @retval string[] Array of article slugs
    public function getSlugs(): array
    {
        return array_keys($this->articles);
    }
}
