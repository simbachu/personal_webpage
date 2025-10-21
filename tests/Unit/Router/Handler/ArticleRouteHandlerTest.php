<?php

declare(strict_types=1);

namespace Tests\Unit\Router\Handler;

use App\Router\Handler\ArticleRouteHandler;
use App\Router\RouteResult;
use App\Type\Route;
use App\Type\TemplateName;
use App\Type\HttpStatusCode;
use PHPUnit\Framework\TestCase;

//! @brief Unit tests for ArticleRouteHandler
//!
//! Tests the article route handler functionality including markdown parsing,
//! metadata extraction, and error handling following the Arrange-Act-Assert pattern.
class ArticleRouteHandlerTest extends TestCase
{
    private string $tempContentDir;
    private ArticleRouteHandler $handler;

    protected function setUp(): void
    {
        // Create a temporary directory for test content
        $this->tempContentDir = sys_get_temp_dir() . '/article_test_' . uniqid();
        mkdir($this->tempContentDir, 0755, true);

        // Create test articles.yaml file
        $this->createTestArticlesYaml();

        $this->handler = new ArticleRouteHandler($this->tempContentDir);
    }

    protected function tearDown(): void
    {
        // Clean up temporary directory
        if (is_dir($this->tempContentDir)) {
            $this->removeDirectory($this->tempContentDir);
        }
    }

    //! @brief Test handling route without article name parameter
    public function testHandleWithoutArticleNameReturnsBadRequest(): void
    {
        // Arrange
        $route = new Route('/read', TemplateName::ARTICLE, [], ['handler' => 'article']);
        $parameters = [];

        // Act
        $result = $this->handler->handle($route, $parameters);

        // Assert
        $this->assertInstanceOf(RouteResult::class, $result);
        $this->assertEquals(TemplateName::NOT_FOUND, $result->getTemplate());
        $this->assertEquals(HttpStatusCode::BAD_REQUEST, $result->getStatusCode());
        $this->assertEquals('Article Not Found', $result->getData()['meta']['title']);
    }

    //! @brief Test handling route with empty article name
    public function testHandleWithEmptyArticleNameReturnsBadRequest(): void
    {
        // Arrange
        $route = new Route('/read', TemplateName::ARTICLE, [], ['handler' => 'article']);
        $parameters = ['article_name' => ''];

        // Act
        $result = $this->handler->handle($route, $parameters);

        // Assert
        $this->assertInstanceOf(RouteResult::class, $result);
        $this->assertEquals(TemplateName::NOT_FOUND, $result->getTemplate());
        $this->assertEquals(HttpStatusCode::BAD_REQUEST, $result->getStatusCode());
        $this->assertEquals('Invalid Article Request', $result->getData()['meta']['title']);
    }

    //! @brief Test handling non-existent article returns 404
    public function testHandleNonExistentArticleReturnsNotFound(): void
    {
        // Arrange
        $route = new Route('/read', TemplateName::ARTICLE, [], ['handler' => 'article']);
        $parameters = ['article_name' => 'nonexistent'];

        // Act
        $result = $this->handler->handle($route, $parameters);

        // Assert
        $this->assertInstanceOf(RouteResult::class, $result);
        $this->assertEquals(TemplateName::NOT_FOUND, $result->getTemplate());
        $this->assertEquals(HttpStatusCode::NOT_FOUND, $result->getStatusCode());
        $this->assertStringContainsString('not found', $result->getData()['meta']['description']);
    }

    //! @brief Test successful article loading with basic markdown
    public function testHandleValidArticleReturnsSuccess(): void
    {
        // Arrange
        $articleContent = "# Test Article\n\nThis is a test article with some content.";
        $this->createTestArticle('test-article', $articleContent);

        $route = new Route('/read', TemplateName::ARTICLE, [], ['handler' => 'article']);
        $parameters = ['article_name' => 'test-article'];

        // Act
        $result = $this->handler->handle($route, $parameters);

        // Assert
        $this->assertInstanceOf(RouteResult::class, $result);
        $this->assertEquals(TemplateName::ARTICLE, $result->getTemplate());
        $this->assertEquals(HttpStatusCode::OK, $result->getStatusCode());

        $article = $result->getData()['article'];
        $this->assertEquals('Test Article', $article['title']);
        $this->assertNull($article['author']);
        $this->assertNull($article['date']);
        $this->assertEquals(['test', 'example'], $article['tags']);
        $this->assertTrue($article['published']);
        $this->assertStringContainsString('<h1>Test Article</h1>', $article['content']);
    }

    //! @brief Test article loading with metadata (By: and On: lines)
    public function testHandleArticleWithMetadataExtractsCorrectly(): void
    {
        // Arrange
        $articleContent = "# Word Rotator\nBy: Jennifer Gott\nOn: 25w39.2\n\n## Presenting a valley\nIn internet parlance...";
        $this->createTestArticle('word-rotator', $articleContent);

        $route = new Route('/read', TemplateName::ARTICLE, [], ['handler' => 'article']);
        $parameters = ['article_name' => 'word-rotator'];

        // Act
        $result = $this->handler->handle($route, $parameters);

        // Assert
        $this->assertInstanceOf(RouteResult::class, $result);
        $this->assertEquals(TemplateName::ARTICLE, $result->getTemplate());

        $article = $result->getData()['article'];
        $this->assertEquals('Word Rotator', $article['title']);
        $this->assertEquals('Jennifer Gott', $article['author']);
        $this->assertEquals('25w39.2', $article['date']);
        $this->assertEquals(['programming', 'tech', 'words'], $article['tags']);
        $this->assertTrue($article['published']);
        $this->assertStringContainsString('<h2>Presenting a valley</h2>', $article['content']);
    }

    //! @brief Test article loading with metadata but no title
    public function testHandleArticleWithMetadataButNoTitleUsesFilename(): void
    {
        // Arrange
        $articleContent = "By: Jennifer Gott\nOn: 25w39.2\n\n## Presenting a valley\nIn internet parlance...";
        $this->createTestArticle('no-title-article', $articleContent);

        $route = new Route('/read', TemplateName::ARTICLE, [], ['handler' => 'article']);
        $parameters = ['article_name' => 'no-title-article'];

        // Act
        $result = $this->handler->handle($route, $parameters);

        // Assert
        $this->assertInstanceOf(RouteResult::class, $result);
        $this->assertEquals(TemplateName::ARTICLE, $result->getTemplate());

        $article = $result->getData()['article'];
        $this->assertEquals('No Title Article', $article['title']); // Uses YAML title
        $this->assertEquals('Jennifer Gott', $article['author']);
        $this->assertEquals('25w39.2', $article['date']);
        $this->assertEquals(['test'], $article['tags']);
        $this->assertTrue($article['published']);
    }

    //! @brief Test meta data generation for article
    public function testMetaDataGeneration(): void
    {
        // Arrange
        $articleContent = "# Test Article\nBy: Test Author\nOn: 2024-01-01\n\nThis is a test article with some content that should be used for the description.";
        $this->createTestArticle('meta-test', $articleContent);

        $route = new Route('/read', TemplateName::ARTICLE, [], ['handler' => 'article']);
        $parameters = ['article_name' => 'meta-test'];

        // Act
        $result = $this->handler->handle($route, $parameters);

        // Assert
        $meta = $result->getData()['meta'];
        $this->assertEquals('Meta Test Article', $meta['title']);
        $this->assertEquals('A test article for metadata', $meta['description']); // Uses YAML description
        $this->assertEquals('Meta Test Article', $meta['og_title']);
        $this->assertStringContainsString('This is a test article', $meta['og_description']);
    }

    //! @brief Test unpublished article returns 404
    public function testUnpublishedArticleReturnsNotFound(): void
    {
        // Arrange
        $this->createTestArticle('unpublished-article', '# Unpublished Article\n\nThis article is not published.');

        $route = new Route('/read', TemplateName::ARTICLE, [], ['handler' => 'article']);
        $parameters = ['article_name' => 'unpublished-article'];

        // Act
        $result = $this->handler->handle($route, $parameters);

        // Assert
        $this->assertInstanceOf(RouteResult::class, $result);
        $this->assertEquals(TemplateName::NOT_FOUND, $result->getTemplate());
        $this->assertEquals(HttpStatusCode::NOT_FOUND, $result->getStatusCode());
        $this->assertStringContainsString('not published', $result->getData()['meta']['description']);
    }

    //! @brief Test security - directory traversal prevention
    public function testDirectoryTraversalPrevention(): void
    {
        // Arrange
        $route = new Route('/read', TemplateName::ARTICLE, [], ['handler' => 'article']);
        $parameters = ['article_name' => '../../../etc/passwd'];

        // Act
        $result = $this->handler->handle($route, $parameters);

        // Assert
        $this->assertInstanceOf(RouteResult::class, $result);
        $this->assertEquals(TemplateName::NOT_FOUND, $result->getTemplate());
        $this->assertEquals(HttpStatusCode::NOT_FOUND, $result->getStatusCode());
    }

    //! @brief Test markdown parsing with footnotes (CommonMark extension)
    public function testMarkdownWithFootnotes(): void
    {
        // Arrange
        $articleContent = "# Test Article\n\nThis is a test with a footnote[^1].\n\n[^1]: This is a footnote.";
        $this->createTestArticle('footnote-test', $articleContent);

        $route = new Route('/read', TemplateName::ARTICLE, [], ['handler' => 'article']);
        $parameters = ['article_name' => 'footnote-test'];

        // Act
        $result = $this->handler->handle($route, $parameters);

        // Assert
        $this->assertInstanceOf(RouteResult::class, $result);
        $this->assertEquals(TemplateName::ARTICLE, $result->getTemplate());

        $article = $result->getData()['article'];
        $this->assertStringContainsString('footnote', $article['content']);
        // Note: The exact HTML output depends on CommonMark's footnote extension
    }

    //! @brief Helper method to create test articles.yaml file
    private function createTestArticlesYaml(): void
    {
        $yamlContent = <<<YAML
- test-article:
    title: Test Article
    file_path: test-article.md
    tags: [test, example]
    published: true

- word-rotator:
    title: Word Rotator
    file_path: word-rotator.md
    tags: [programming, tech, words]
    published: true

- no-title-article:
    title: No Title Article
    file_path: no-title-article.md
    tags: [test]
    published: true

- meta-test:
    title: Meta Test Article
    file_path: meta-test.md
    description: "A test article for metadata"
    tags: [test, metadata]
    published: true

- footnote-test:
    title: Footnote Test
    file_path: footnote-test.md
    tags: [test, footnotes]
    published: true

- unpublished-article:
    title: Unpublished Article
    file_path: unpublished-article.md
    tags: [test]
    published: false
YAML;

        file_put_contents($this->tempContentDir . '/articles.yaml', $yamlContent);
    }

    //! @brief Helper method to create a test article file
    private function createTestArticle(string $name, string $content): void
    {
        $filePath = $this->tempContentDir . '/' . $name . '.md';
        file_put_contents($filePath, $content);
    }

    //! @brief Helper method to recursively remove directory
    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
}
