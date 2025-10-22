<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\Article;
use App\Service\MarkdownProcessor;
use Symfony\Component\Yaml\Yaml;

//! @brief File-based article repository that reads from articles.yaml and markdown files
final class FileArticleRepository implements ArticleRepository
{
    private ?array $articlesConfig = null;

    //! @brief Construct the file-based article repository
    //! @param contentPath Path to the content directory
    //! @param markdownProcessor Service for processing markdown content
    public function __construct(
        private readonly string $contentPath,
        private readonly MarkdownProcessor $markdownProcessor
    ) {}

    //! @brief Find all published articles
    //! @retval Article[] Array of published articles
    public function findPublished(): array
    {
        $articles = [];
        $config = $this->getArticlesConfig();

        foreach ($config as $slug => $articleConfig) {
            if ($articleConfig['published'] ?? false) {
                $article = $this->findBySlug($slug);
                if ($article) {
                    $articles[] = $article;
                }
            }
        }

        return $articles;
    }

    //! @brief Find an article by its slug
    //! @param slug The article slug
    //! @retval Article|null The article if found and published, null otherwise
    public function findBySlug(string $slug): ?Article
    {
        $config = $this->getArticlesConfig();

        if (!isset($config[$slug])) {
            return null;
        }

        $articleConfig = $config[$slug];

        if (!($articleConfig['published'] ?? false)) {
            return null;
        }

        $filePath = $this->contentPath . '/' . ($articleConfig['file_path'] ?? $slug . '.md');

        if (!file_exists($filePath)) {
            return null;
        }

        $markdownContent = file_get_contents($filePath);
        if ($markdownContent === false) {
            return null;
        }

        // Parse the markdown content
        $parsedContent = $this->markdownProcessor->process($markdownContent);

        return new Article(
            slug: $slug,
            title: $articleConfig['title'] ?? $parsedContent['title'] ?? $slug,
            author: $parsedContent['author'] ?? null,
            date: $parsedContent['date'] ?? null,
            content: $parsedContent['content'],
            tags: $articleConfig['tags'] ?? [],
            published: $articleConfig['published'] ?? false,
            description: $articleConfig['description'] ?? null,
            footnotes: $parsedContent['footnotes'] ?? []
        );
    }

    //! @brief Check if an article exists
    //! @param slug The article slug
    //! @retval bool True if the article exists, false otherwise
    public function exists(string $slug): bool
    {
        $config = $this->getArticlesConfig();
        return isset($config[$slug]);
    }

    //! @brief Get all articles (published and unpublished)
    //! @retval Article[] Array of all articles
    public function findAll(): array
    {
        $articles = [];
        $config = $this->getArticlesConfig();

        foreach ($config as $slug => $articleConfig) {
            $article = $this->findBySlug($slug);
            if ($article) {
                $articles[] = $article;
            }
        }

        return $articles;
    }

    //! @brief Load and cache the articles configuration
    //! @retval array The articles configuration array
    private function getArticlesConfig(): array
    {
        if ($this->articlesConfig === null) {
            $yamlPath = $this->contentPath . '/articles.yaml';

            if (!file_exists($yamlPath)) {
                $this->articlesConfig = [];
                return $this->articlesConfig;
            }

            $yamlContent = file_get_contents($yamlPath);
            if ($yamlContent === false) {
                throw new \RuntimeException('Could not read articles configuration file');
            }

            $config = Yaml::parse($yamlContent);

            // Handle the list format: - article-name: { ... }
            if (is_array($config) && isset($config[0]) && is_array($config[0])) {
                // Convert list format to associative array
                $this->articlesConfig = [];
                foreach ($config as $item) {
                    if (is_array($item)) {
                        $this->articlesConfig = array_merge($this->articlesConfig, $item);
                    }
                }
            } else {
                $this->articlesConfig = $config;
            }
        }

        return $this->articlesConfig;
    }

}
