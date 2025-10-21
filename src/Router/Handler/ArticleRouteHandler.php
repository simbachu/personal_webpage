<?php

declare(strict_types=1);

namespace App\Router\Handler;

use App\Router\RouteHandler;
use App\Router\RouteResult;
use App\Type\Route;
use App\Type\TemplateName;
use App\Type\HttpStatusCode;
use App\Type\FilePath;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\Footnote\FootnoteExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use Symfony\Component\Yaml\Yaml;

//! @brief Route handler for article/blog routes
//!
//! This handler processes article routes by reading markdown files from the content
//! directory, parsing them with CommonMark, and extracting metadata from the frontmatter.
class ArticleRouteHandler implements RouteHandler
{
    private string $contentPath;
    private ?array $articlesConfig = null;

    //! @brief Construct the article route handler
    //! @param contentPath Path to the content directory containing markdown files
    public function __construct(string $contentPath)
    {
        $this->contentPath = $contentPath;
    }

    //! @brief Handle article routes
    //! @param route The matched route
    //! @param parameters Route parameters (may contain 'article_name')
    //! @return RouteResult The result containing article data or error
    public function handle(Route $route, array $parameters = []): RouteResult
    {
        // Handle /read, /article, or /blog routes (no specific article)
        if (empty($parameters) || !isset($parameters['article_name'])) {
            // TODO: Return article listing page
            return new RouteResult(
                TemplateName::NOT_FOUND,
                [
                    'meta' => [
                        'title' => 'Article Not Found',
                        'description' => 'No article specified.',
                    ]
                ],
                HttpStatusCode::BAD_REQUEST
            );
        }

        // Handle /read/{article_name}, /article/{article_name}, or /blog/{article_name} routes
        $articleName = $parameters['article_name'];
        if ($articleName === '') {
            return new RouteResult(
                TemplateName::NOT_FOUND,
                [
                    'meta' => [
                        'title' => 'Invalid Article Request',
                        'description' => 'No article specified.',
                    ]
                ],
                HttpStatusCode::BAD_REQUEST
            );
        }

        try {
            // Load and parse the article using YAML configuration
            $articleData = $this->loadArticle($articleName);

            return new RouteResult(
                TemplateName::ARTICLE,
                [
                    'article' => $articleData,
                    'meta' => $this->generateMetaData($articleData)
                ]
            );
        } catch (\RuntimeException $e) {
            // Handle article load failure
            return new RouteResult(
                TemplateName::NOT_FOUND,
                [
                    'meta' => [
                        'title' => 'Article Not Found',
                        'description' => $e->getMessage(),
                    ]
                ],
                HttpStatusCode::NOT_FOUND
            );
        }
    }

    //! @brief Load and parse a markdown article file using YAML configuration
    //! @param articleName The name of the article (slug from URL)
    //! @return array{title: string, author: string|null, date: string|null, content: string, tags: array, description: string|null} Parsed article data
    //! @throws \RuntimeException If the article file cannot be found or parsed
    private function loadArticle(string $articleName): array
    {
        // Sanitize article name to prevent directory traversal
        $articleName = basename($articleName);

        // Load articles configuration
        $articlesConfig = $this->getArticlesConfig();

        // Check if article exists in configuration
        if (!isset($articlesConfig[$articleName])) {
            throw new \RuntimeException("Article '{$articleName}' not found in articles configuration.");
        }

        $articleConfig = $articlesConfig[$articleName];

        // Check if article is published
        if (!($articleConfig['published'] ?? false)) {
            throw new \RuntimeException("Article '{$articleName}' is not published.");
        }

        // Get file path from configuration
        $filePath = $this->contentPath . '/' . $articleConfig['file_path'];

        if (!file_exists($filePath)) {
            throw new \RuntimeException("Article file '{$articleConfig['file_path']}' not found.");
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new \RuntimeException("Could not read article file '{$articleConfig['file_path']}'.");
        }

        // Parse markdown content
        $parsedContent = $this->parseMarkdown($content, $articleConfig['title'] ?? $articleName);

        // Merge YAML configuration with parsed content
        return array_merge($parsedContent, [
            'title' => $articleConfig['title'] ?? $parsedContent['title'],
            'tags' => $articleConfig['tags'] ?? [],
            'description' => $articleConfig['description'] ?? null,
            'published' => $articleConfig['published'] ?? false,
        ]);
    }

    //! @brief Load articles configuration from YAML file
    //! @return array Articles configuration array
    //! @throws \RuntimeException If the articles.yaml file cannot be loaded
    private function getArticlesConfig(): array
    {
        if ($this->articlesConfig === null) {
            $yamlPath = $this->contentPath . '/articles.yaml';

            if (!file_exists($yamlPath)) {
                throw new \RuntimeException('Articles configuration file not found: articles.yaml');
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

    //! @brief Parse markdown content and extract metadata
    //! @param content The raw markdown content
    //! @param articleName The article name for fallback title
    //! @return array{title: string, author: string|null, date: string|null, content: string} Parsed article data
    private function parseMarkdown(string $content, string $articleName): array
    {
        $lines = explode("\n", $content);
        $title = $articleName; // Default fallback
        $author = null;
        $date = null;
        $contentStart = null;
        $hasMetadata = false;

        // Parse frontmatter (first few lines for metadata)
        for ($i = 0; $i < min(10, count($lines)); $i++) {
            $line = trim($lines[$i]);

            // Skip empty lines
            if ($line === '') {
                continue;
            }

            // Check for title (first # heading)
            if (str_starts_with($line, '# ')) {
                $title = trim(substr($line, 2));
                continue;
            }

            // Check for metadata lines (By: and On:)
            if (str_starts_with($line, 'By: ')) {
                $author = trim(substr($line, 4));
                $hasMetadata = true;
                continue;
            }

            if (str_starts_with($line, 'On: ')) {
                $date = trim(substr($line, 4));
                $hasMetadata = true;
                continue;
            }

            // If we hit content that's not metadata, start from here
            // But only if we've already found metadata, otherwise include the title
            if (!str_starts_with($line, 'By: ') && !str_starts_with($line, 'On: ') && !str_starts_with($line, '# ')) {
                if ($hasMetadata) {
                    $contentStart = $i;
                    break;
                } else {
                    // No metadata found, so include the title in content
                    $contentStart = 0;
                    break;
                }
            }
        }

        // Get the content starting from the first non-metadata line
        // If no content start was found, start from the beginning
        if ($contentStart === null) {
            $contentStart = 0;
        }

        // If we have metadata, we want to exclude the title and metadata from content
        // If we don't have metadata, we want to include the title in content
        if ($hasMetadata && $contentStart === 0) {
            // Find the first non-metadata line after title and metadata
            for ($i = 0; $i < count($lines); $i++) {
                $line = trim($lines[$i]);
                if ($line !== '' && !str_starts_with($line, '# ') && !str_starts_with($line, 'By: ') && !str_starts_with($line, 'On: ')) {
                    $contentStart = $i;
                    break;
                }
            }
        } elseif (!$hasMetadata && $contentStart === 0) {
            // If no metadata, include the title in content by starting from the beginning
            $contentStart = 0;
        }

        $contentLines = array_slice($lines, $contentStart);
        $markdownContent = implode("\n", $contentLines);

        // Configure CommonMark with extensions
        $environment = new Environment();
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new GithubFlavoredMarkdownExtension());
        $environment->addExtension(new FootnoteExtension());

        $converter = new CommonMarkConverter([], $environment);
        $htmlContent = $converter->convert($markdownContent);

        return [
            'title' => $title,
            'author' => $author,
            'date' => $date,
            'content' => $htmlContent->getContent()
        ];
    }

    //! @brief Generate comprehensive meta data for article pages
    //! @param article Array containing article data
    //! @return array{title: string, description: string, og_title: string, og_description: string} Meta data for template
    private function generateMetaData(array $article): array
    {
        $title = $article['title'];
        $author = $article['author'] ?? '';

        // Use YAML description if available, otherwise generate from author and title
        $description = $article['description'] ?? ($author ? "Article by {$author} - {$title}" : $title);

        // Generate a short description from content (first 150 chars)
        $contentText = strip_tags($article['content']);
        $shortDescription = mb_substr($contentText, 0, 150);
        if (mb_strlen($contentText) > 150) {
            $shortDescription .= '...';
        }

        return [
            'title' => $title,
            'description' => $description,
            'og_title' => $title,
            'og_description' => $shortDescription,
        ];
    }
}
