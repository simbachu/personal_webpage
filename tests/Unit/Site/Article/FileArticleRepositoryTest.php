<?php

declare(strict_types=1);

namespace Tests\Unit\Site\Article;

use App\Site\Article\FileArticleRepository;
use App\Site\Article\MarkdownProcessor;
use PHPUnit\Framework\TestCase;

//! @brief Unit tests for FileArticleRepository config loading and slug lookup
final class FileArticleRepositoryTest extends TestCase
{
    private string $contentPath;

    protected function setUp(): void
    {
        $this->contentPath = sys_get_temp_dir() . '/articles_repo_' . uniqid();
        mkdir($this->contentPath, 0777, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->contentPath . '/*') ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        if (is_dir($this->contentPath)) {
            rmdir($this->contentPath);
        }
    }

    public function test_find_by_slug_returns_published_article_from_list_yaml(): void
    {
        //! @section Arrange
        $this->writeArticlesYaml(<<<'YAML'
- hello-world:
    title: Hello From Config
    published: true
    file_path: hello.md
    tags: [php]
    description: A short blurb
YAML);
        file_put_contents($this->contentPath . '/hello.md', <<<'MD'
# Markdown Title
By: Author
On: 2024-02-01

Hello body.
MD);

        $repository = new FileArticleRepository($this->contentPath, new MarkdownProcessor());

        //! @section Act
        $article = $repository->findBySlug('hello-world');

        //! @section Assert
        $this->assertNotNull($article);
        $this->assertSame('hello-world', $article->slug);
        $this->assertSame('Hello From Config', $article->title);
        $this->assertSame('Author', $article->author);
        $this->assertSame('2024-02-01', $article->date);
        $this->assertSame(['php'], $article->tags);
        $this->assertTrue($article->published);
        $this->assertSame('A short blurb', $article->description);
        $this->assertStringContainsString('Hello body', $article->content);
        $this->assertTrue($repository->exists('hello-world'));
    }

    public function test_find_by_slug_returns_null_for_unpublished_or_missing(): void
    {
        //! @section Arrange
        $this->writeArticlesYaml(<<<'YAML'
draft-piece:
  title: Draft
  published: false
  file_path: draft.md
YAML);
        file_put_contents($this->contentPath . '/draft.md', "# Draft\n\nSecret.\n");
        $repository = new FileArticleRepository($this->contentPath, new MarkdownProcessor());

        //! @section Act / Assert
        $this->assertNull($repository->findBySlug('draft-piece'));
        $this->assertNull($repository->findBySlug('missing'));
        $this->assertTrue($repository->exists('draft-piece'));
        $this->assertFalse($repository->exists('missing'));
    }

    public function test_find_by_slug_returns_null_when_markdown_file_missing(): void
    {
        //! @section Arrange
        $this->writeArticlesYaml(<<<'YAML'
broken:
  title: Broken
  published: true
  file_path: does-not-exist.md
YAML);
        $repository = new FileArticleRepository($this->contentPath, new MarkdownProcessor());

        //! @section Act / Assert
        $this->assertNull($repository->findBySlug('broken'));
    }

    public function test_find_published_returns_only_published_articles(): void
    {
        //! @section Arrange
        $this->writeArticlesYaml(<<<'YAML'
- live:
    title: Live
    published: true
    file_path: live.md
- draft:
    title: Draft
    published: false
    file_path: draft.md
YAML);
        file_put_contents($this->contentPath . '/live.md', "# Live\n\nOk.\n");
        file_put_contents($this->contentPath . '/draft.md', "# Draft\n\nNo.\n");
        $repository = new FileArticleRepository($this->contentPath, new MarkdownProcessor());

        //! @section Act
        $published = $repository->findPublished();
        $allViaFindAll = $repository->findAll();

        //! @section Assert
        $this->assertCount(1, $published);
        $this->assertSame('live', $published[0]->slug);
        $this->assertCount(1, $allViaFindAll);
    }

    public function test_missing_articles_yaml_yields_empty_config_behavior(): void
    {
        //! @section Arrange
        $repository = new FileArticleRepository($this->contentPath, new MarkdownProcessor());

        //! @section Act / Assert
        $this->assertSame([], $repository->findPublished());
        $this->assertSame([], $repository->findAll());
        $this->assertNull($repository->findBySlug('anything'));
    }

    public function test_associative_yaml_format_is_supported(): void
    {
        //! @section Arrange
        $this->writeArticlesYaml(<<<'YAML'
assoc-article:
  title: Assoc
  published: true
  file_path: assoc.md
YAML);
        file_put_contents($this->contentPath . '/assoc.md', "# Assoc\n\nBody.\n");
        $repository = new FileArticleRepository($this->contentPath, new MarkdownProcessor());

        //! @section Act
        $article = $repository->findBySlug('assoc-article');

        //! @section Assert
        $this->assertNotNull($article);
        $this->assertSame('Assoc', $article->title);
    }

    //! @brief Write articles.yaml into the temp content directory
    private function writeArticlesYaml(string $yaml): void
    {
        file_put_contents($this->contentPath . '/articles.yaml', $yaml);
    }
}
