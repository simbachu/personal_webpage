<?php

declare(strict_types=1);

namespace Tests\Unit\Router\Handler;

use App\Model\Article;
use App\Repository\TestArticleRepository;
use App\Router\Handler\ArticleRouteHandler;
use App\Router\RouteResult;
use App\Type\Route;
use App\Type\TemplateName;
use App\Type\HttpStatusCode;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ArticleRouteHandler
 *
 * Tests the article route handler functionality using the TestArticleRepository
 * for isolated testing without file system dependencies.
 */
class ArticleRouteHandlerTest extends TestCase
{
    private TestArticleRepository $repository;
    private ArticleRouteHandler $handler;

    protected function setUp(): void
    {
        $this->repository = new TestArticleRepository();
        $this->handler = new ArticleRouteHandler($this->repository);
    }

    protected function tearDown(): void
    {
        $this->repository->clear();
    }

    /**
     * Test handling a non-existent article returns 404
     */
    public function testHandleNonExistentArticleReturnsNotFound(): void
    {
        // Arrange
        $route = new Route('/read', TemplateName::ARTICLE, [], ['handler' => 'article']);
        $parameters = ['article_name' => 'non-existent'];

        // Act
        $result = $this->handler->handle($route, $parameters);

        // Assert
        $this->assertInstanceOf(RouteResult::class, $result);
        $this->assertEquals(TemplateName::NOT_FOUND, $result->getTemplate());
        $this->assertEquals(HttpStatusCode::NOT_FOUND, $result->getStatusCode());
        $this->assertStringContainsString('not found', $result->getData()['meta']['description']);
    }

    /**
     * Test handling a valid published article returns success
     */
    public function testHandleValidArticleReturnsSuccess(): void
    {
        // Arrange
        $article = new Article(
            slug: 'test-article',
            title: 'Test Article',
            author: null,
            date: null,
            content: '<h2>Test Article</h2><p>This is a test article with some content.</p>',
            tags: ['test', 'example'],
            published: true,
            description: null
        );
        $this->repository->addArticle($article);

        $route = new Route('/read', TemplateName::ARTICLE, [], ['handler' => 'article']);
        $parameters = ['article_name' => 'test-article'];

        // Act
        $result = $this->handler->handle($route, $parameters);

        // Assert
        $this->assertInstanceOf(RouteResult::class, $result);
        $this->assertEquals(TemplateName::ARTICLE, $result->getTemplate());
        $this->assertEquals(HttpStatusCode::OK, $result->getStatusCode());

        $articleData = $result->getData()['article'];
        $this->assertEquals('Test Article', $articleData['title']);
        $this->assertNull($articleData['author']);
        $this->assertNull($articleData['date']);
        $this->assertEquals(['test', 'example'], $articleData['tags']);
        $this->assertTrue($articleData['published']);
        $this->assertStringContainsString('<h2>Test Article</h2>', $articleData['content']);
    }

    /**
     * Test handling an article with metadata extracts correctly
     */
    public function testHandleArticleWithMetadataExtractsCorrectly(): void
    {
        // Arrange
        $article = new Article(
            slug: 'word-rotator',
            title: 'Word Rotator',
            author: 'Jennifer Gott',
            date: '25w39.2',
            content: '<h3>Presenting a valley</h3><p>In internet parlance...</p>',
            tags: ['programming', 'tech', 'words'],
            published: true,
            description: null
        );
        $this->repository->addArticle($article);

        $route = new Route('/read', TemplateName::ARTICLE, [], ['handler' => 'article']);
        $parameters = ['article_name' => 'word-rotator'];

        // Act
        $result = $this->handler->handle($route, $parameters);

        // Assert
        $this->assertInstanceOf(RouteResult::class, $result);
        $this->assertEquals(TemplateName::ARTICLE, $result->getTemplate());

        $articleData = $result->getData()['article'];
        $this->assertEquals('Word Rotator', $articleData['title']);
        $this->assertEquals('Jennifer Gott', $articleData['author']);
        $this->assertEquals('25w39.2', $articleData['date']);
        $this->assertEquals(['programming', 'tech', 'words'], $articleData['tags']);
        $this->assertTrue($articleData['published']);
        $this->assertStringContainsString('<h3>Presenting a valley</h3>', $articleData['content']);
    }

    /**
     * Test handling an unpublished article returns 404
     */
    public function testHandleUnpublishedArticleReturnsNotFound(): void
    {
        // Arrange
        $article = new Article(
            slug: 'unpublished-article',
            title: 'Unpublished Article',
            author: null,
            date: null,
            content: '<h2>Unpublished Article</h2><p>This article is not published.</p>',
            tags: ['test'],
            published: false,
            description: null
        );
        $this->repository->addArticle($article);

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

    /**
     * Test security - directory traversal prevention
     */
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

    /**
     * Test meta data generation for article
     */
    public function testMetaDataGeneration(): void
    {
        // Arrange
        $article = new Article(
            slug: 'meta-test',
            title: 'Meta Test Article',
            author: 'Test Author',
            date: null,
            content: '<h2>Meta Test Article</h2><p>This is a test article for metadata.</p>',
            tags: ['test', 'metadata'],
            published: true,
            description: 'A test article for metadata'
        );
        $this->repository->addArticle($article);

        $route = new Route('/read', TemplateName::ARTICLE, [], ['handler' => 'article']);
        $parameters = ['article_name' => 'meta-test'];

        // Act
        $result = $this->handler->handle($route, $parameters);

        // Assert
        $meta = $result->getData()['meta'];
        $this->assertEquals('Meta Test Article', $meta['title']);
        $this->assertEquals('A test article for metadata', $meta['description']); // Uses YAML description
        $this->assertEquals('Meta Test Article', $meta['og_title']);
        $this->assertStringContainsString('test article for metadata', $meta['og_description']);
    }

    /**
     * Test empty article name returns 404
     */
    public function testEmptyArticleNameReturnsNotFound(): void
    {
        // Arrange
        $route = new Route('/read', TemplateName::ARTICLE, [], ['handler' => 'article']);
        $parameters = ['article_name' => ''];

        // Act
        $result = $this->handler->handle($route, $parameters);

        // Assert
        $this->assertInstanceOf(RouteResult::class, $result);
        $this->assertEquals(TemplateName::NOT_FOUND, $result->getTemplate());
        $this->assertEquals(HttpStatusCode::NOT_FOUND, $result->getStatusCode());
    }

    /**
     * Test invalid article name characters are sanitized
     */
    public function testInvalidArticleNameCharactersAreSanitized(): void
    {
        // Arrange
        $route = new Route('/read', TemplateName::ARTICLE, [], ['handler' => 'article']);
        $parameters = ['article_name' => 'test@#$%^&*()'];

        // Act
        $result = $this->handler->handle($route, $parameters);

        // Assert
        $this->assertInstanceOf(RouteResult::class, $result);
        $this->assertEquals(TemplateName::NOT_FOUND, $result->getTemplate());
        $this->assertEquals(HttpStatusCode::NOT_FOUND, $result->getStatusCode());
    }
}