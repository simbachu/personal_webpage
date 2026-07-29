<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\Article;

//! @brief Repository interface for article data access
interface ArticleRepository
{
    //! @brief Find all published articles
    //! @retval Article[] Array of published articles
    public function findPublished(): array;

    //! @brief Find an article by its slug
    //! @param slug The article slug
    //! @retval Article|null The article if found, null otherwise
    public function findBySlug(string $slug): ?Article;

    //! @brief Check if an article exists
    //! @param slug The article slug
    //! @retval bool True if the article exists, false otherwise
    public function exists(string $slug): bool;

    //! @brief Get all articles (published and unpublished)
    //! @retval Article[] Array of all articles
    public function findAll(): array;
}
