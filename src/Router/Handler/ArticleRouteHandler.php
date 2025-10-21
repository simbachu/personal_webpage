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
        $config = [
            'footnote' => [
                'backref_class' => 'footnote-backref',
                'backref_symbol' => '↩',
                'container_add_hr' => true,
                'container_class' => 'footnotes',
                'ref_class' => 'footnote-ref',
                'ref_id_prefix' => 'fnref:',
                'footnote_class' => 'footnote',
                'footnote_id_prefix' => 'fn:',
            ]
        ];

        $environment = new Environment($config);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new GithubFlavoredMarkdownExtension());
        $environment->addExtension(new FootnoteExtension());

        $converter = new CommonMarkConverter($config, $environment);
        $htmlContent = $converter->convert($markdownContent);

        // Post-process the HTML to adjust heading levels and add superscript support
        $processedContent = $this->postProcessHtml($htmlContent->getContent());

        // Extract footnotes from the processed content
        $footnotes = $this->extractFootnotes($processedContent);
        $contentWithoutFootnotes = $this->removeFootnotesSection($processedContent);

        return [
            'title' => $title,
            'author' => $author,
            'date' => $date,
            'content' => $contentWithoutFootnotes,
            'footnotes' => $footnotes
        ];
    }

    //! @brief Post-process HTML to adjust heading levels, add superscript support, and handle footnotes
    //! @param html The HTML content to process
    //! @return string Processed HTML content
    private function postProcessHtml(string $html): string
    {
        // Adjust heading levels (h1 -> h2, h2 -> h3, etc.)
        $html = preg_replace_callback('/<h([1-6])>/', function($matches) {
            $level = (int)$matches[1];
            $newLevel = min($level + 1, 6); // Don't go beyond h6
            return "<h{$newLevel}>";
        }, $html);

        $html = preg_replace_callback('/<\/h([1-6])>/', function($matches) {
            $level = (int)$matches[1];
            $newLevel = min($level + 1, 6); // Don't go beyond h6
            return "</h{$newLevel}>";
        }, $html);

        // Handle footnotes manually since the extension isn't working (do this before superscript)
        $html = $this->processFootnotes($html);

        // Convert ^<a href="url">text</a>^ to <sup><a href="url">text</a></sup> (superscripted hyperlinks)
        $html = preg_replace('/\^<a href="([^"]+)">([^<]+)<\/a>\^/', '<sup><a href="$1">$2</a></sup>', $html);

        // Convert ^text^ to <sup>text</sup> (but not if it's already processed as a footnote or hyperlink)
        $html = preg_replace('/\^([^<>\n\[\]]+?)\^/', '<sup>$1</sup>', $html);

        return $html;
    }

    //! @brief Process footnotes manually since the CommonMark extension isn't working
    //! @param html The HTML content to process
    //! @return string HTML with processed footnotes
    private function processFootnotes(string $html): string
    {
        // Find all footnote definitions [^id]: text
        $footnotes = [];

        // Extract all footnote definitions from paragraphs
        $html = preg_replace_callback('/<p>\[(\^[^\]]+)\]:\s*(.+?)<\/p>/s', function($matches) use (&$footnotes) {
            $firstId = $matches[1];
            $fullText = $matches[2];

            // The first footnote starts immediately after the colon
            $currentFootnote = $firstId;
            $currentText = '';

            // Split by lines and process each footnote definition
            $lines = explode("\n", $fullText);

            foreach ($lines as $line) {
                $line = trim($line);
                if (preg_match('/^\[(\^[^\]]+)\]:\s*(.*)$/', $line, $lineMatches)) {
                    // Save previous footnote if exists
                    if ($currentFootnote !== null) {
                        $footnotes[$currentFootnote] = trim($currentText);
                    }

                    // Start new footnote
                    $currentFootnote = $lineMatches[1];
                    $currentText = $lineMatches[2];
                } else {
                    // Continue current footnote text
                    if ($currentFootnote !== null && $line !== '') {
                        $currentText .= ($currentText ? "\n" : '') . $line;
                    }
                }
            }

            // Save last footnote
            if ($currentFootnote !== null) {
                $footnotes[$currentFootnote] = trim($currentText);
            }

            return ''; // Remove the paragraph
        }, $html);

        // Replace footnote references [^id] with links
        $footnoteCounter = 1;
        $processedFootnotes = [];

        $html = preg_replace_callback('/\[(\^[^\]]+)\]/', function($matches) use (&$footnotes, &$footnoteCounter, &$processedFootnotes) {
            $id = $matches[1];
            if (isset($footnotes[$id])) {
                $counter = $footnoteCounter++;
                $processedFootnotes[$counter] = $footnotes[$id];
                return '<sup><a href="#fn:' . $counter . '" id="fnref:' . $counter . '" class="footnote-ref">' . $counter . '</a></sup>';
            }
            return $matches[0]; // Return original if not found
        }, $html);

        // Add footnotes section if we have any
        if (!empty($processedFootnotes)) {
            $footnotesHtml = '<hr><section class="footnotes">';
            foreach ($processedFootnotes as $num => $text) {
                $footnotesHtml .= '<div class="footnote" id="fn:' . $num . '">';
                $footnotesHtml .= '<p>' . $text . ' <a href="#fnref:' . $num . '" class="footnote-backref">↩</a></p>';
                $footnotesHtml .= '</div>';
            }
            $footnotesHtml .= '</section>';
            $html .= $footnotesHtml;
        }

        return $html;
    }

    //! @brief Extract footnotes from processed HTML content
    //! @param html The HTML content with footnotes
    //! @return array Array of footnotes with id => content pairs
    private function extractFootnotes(string $html): array
    {
        $footnotes = [];

        // Find the footnotes section
        if (preg_match('/<hr><section class="footnotes">(.*?)<\/section>/s', $html, $matches)) {
            $footnotesSection = $matches[1];

            // Extract individual footnotes
            preg_match_all('/<div class="footnote" id="fn:(\d+)"><p>(.*?)<\/p><\/div>/s', $footnotesSection, $footnoteMatches, PREG_SET_ORDER);

            foreach ($footnoteMatches as $match) {
                $id = (int)$match[1];
                $content = $match[2];

                // Remove the back-reference link from the content
                $content = preg_replace('/ <a href="#fnref:\d+" class="footnote-backref">↩<\/a>$/', '', $content);

                $footnotes[$id] = trim($content);
            }
        }

        return $footnotes;
    }

    //! @brief Remove the footnotes section from HTML content
    //! @param html The HTML content with footnotes section
    //! @return string HTML content without footnotes section
    private function removeFootnotesSection(string $html): string
    {
        // Remove the entire footnotes section
        return preg_replace('/<hr><section class="footnotes">.*?<\/section>/s', '', $html);
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
