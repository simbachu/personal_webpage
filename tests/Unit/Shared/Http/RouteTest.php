<?php

declare(strict_types=1);

namespace Tests\Unit\Router;

use PHPUnit\Framework\TestCase;
use App\Type\Route;
use App\Type\TemplateName;

//! @brief Unit tests for Route value object
class RouteTest extends TestCase
{
    //! @brief Test basic route creation
    public function test_can_create_route_with_basic_properties(): void
    {
        //! @section Arrange
        $template = TemplateName::HOME;
        $meta = ['title' => 'Home Page'];

        //! @section Act
        $route = new Route('/', $template, $meta);

        //! @section Assert
        $this->assertEquals('/', $route->getPath());
        $this->assertEquals($template, $route->getTemplate());
        $this->assertEquals($meta, $route->getMeta());
        $this->assertEmpty($route->getOptions());
    }

    //! @brief Test route with options
    public function test_can_create_route_with_options(): void
    {
        //! @section Arrange
        $options = ['handler' => 'home'];

        //! @section Act
        $route = new Route('/', TemplateName::HOME, [], $options);

        //! @section Assert
        $this->assertEquals($options, $route->getOptions());
        $this->assertEquals('home', $route->getOption('handler'));
        $this->assertNull($route->getOption('nonexistent'));
        $this->assertEquals('default', $route->getOption('nonexistent', 'default'));
    }

    //! @brief Test route path normalization
    public function test_route_path_is_normalized(): void
    {
        //! @section Arrange & Act
        $route1 = new Route('/', TemplateName::HOME);
        $route2 = new Route('/about/', TemplateName::HOME);

        //! @section Assert
        $this->assertEquals('/', $route1->getPath());
        $this->assertEquals('/about', $route2->getPath());
    }

    //! @brief Test exact path matching
    public function test_exact_path_matching(): void
    {
        //! @section Arrange
        $route = new Route('/about', TemplateName::HOME);

        //! @section Act
        $matchesAbout = $route->matches('/about');
        $matchesAboutWithSlash = $route->matches('/about/');
        $matchesRoot = $route->matches('/');
        $matchesAboutWithExtra = $route->matches('/about/extra');

        //! @section Assert
        $this->assertTrue($matchesAbout);
        $this->assertTrue($matchesAboutWithSlash);
        $this->assertFalse($matchesRoot);
        $this->assertFalse($matchesAboutWithExtra);
    }

    //! @brief Test dynamic path matching for dex routes
    public function test_dynamic_path_matching_for_dex(): void
    {
        //! @section Arrange
        $route = new Route('/dex', TemplateName::DEX);

        //! @section Act
        $matchesDex = $route->matches('/dex');
        $matchesDexPikachu = $route->matches('/dex/pikachu');
        $matchesDex25 = $route->matches('/dex/25');
        $matchesDexPikachuExtra = $route->matches('/dex/pikachu/extra');
        $matchesPokemon = $route->matches('/pokemon');

        //! @section Assert
        $this->assertTrue($matchesDex);
        $this->assertTrue($matchesDexPikachu);
        $this->assertTrue($matchesDex25);
        $this->assertFalse($matchesDexPikachuExtra);
        $this->assertFalse($matchesPokemon);
    }

    //! @brief Test parameter extraction from dynamic routes
    public function test_parameter_extraction_from_dynamic_routes(): void
    {
        //! @section Arrange
        $route = new Route('/dex', TemplateName::DEX);

        //! @section Act
        $paramsForDex = $route->extractParameters('/dex');
        $paramsForDexPikachu = $route->extractParameters('/dex/pikachu');
        $paramsForDex25 = $route->extractParameters('/dex/25');
        $paramsForRoot = $route->extractParameters('/');

        //! @section Assert
        $this->assertEquals([], $paramsForDex);
        $this->assertEquals(['id_or_name' => 'pikachu'], $paramsForDexPikachu);
        $this->assertEquals(['id_or_name' => '25'], $paramsForDex25);
        $this->assertEquals([], $paramsForRoot);
    }

    //! @brief Test metadata access methods
    public function test_metadata_access_methods(): void
    {
        //! @section Arrange
        $meta = ['title' => 'Test Title', 'description' => 'Test Description'];
        $route = new Route('/', TemplateName::HOME, $meta);

        //! @section Act
        $titleValue = $route->getMetaValue('title');
        $descriptionValue = $route->getMetaValue('description');
        $nonexistentValue = $route->getMetaValue('nonexistent');

        //! @section Assert
        $this->assertEquals('Test Title', $titleValue);
        $this->assertEquals('Test Description', $descriptionValue);
        $this->assertNull($nonexistentValue);

        //! @section Act
        $nonexistentWithDefault = $route->getMetaValue('nonexistent', 'default');

        //! @section Assert
        $this->assertEquals('default', $nonexistentWithDefault);
    }

    //! @brief Test route cloning with merged metadata
    public function test_route_cloning_with_merged_metadata(): void
    {
        //! @section Arrange
        $originalMeta = ['title' => 'Original Title'];
        $route = new Route('/', TemplateName::HOME, $originalMeta);

        //! @section Act
        $newRoute = $route->withMeta(['description' => 'New Description']);

        //! @section Assert
        $this->assertEquals('Original Title', $newRoute->getMetaValue('title'));
        $this->assertEquals('New Description', $newRoute->getMetaValue('description'));
        $this->assertEquals('/', $newRoute->getPath());
        $this->assertEquals(TemplateName::HOME, $newRoute->getTemplate());

        // Original route should be unchanged
        $this->assertArrayNotHasKey('description', $route->getMeta());
    }

    //! @brief Test route cloning with merged options
    public function test_route_cloning_with_merged_options(): void
    {
        //! @section Arrange
        $originalOptions = ['handler' => 'home'];
        $route = new Route('/', TemplateName::HOME, [], $originalOptions);

        //! @section Act
        $newRoute = $route->withOptions(['cache' => 'true']);

        //! @section Assert
        $this->assertEquals('home', $newRoute->getOption('handler'));
        $this->assertEquals('true', $newRoute->getOption('cache'));
        $this->assertEquals('/', $newRoute->getPath());
        $this->assertEquals(TemplateName::HOME, $newRoute->getTemplate());

        // Original route should be unchanged
        $this->assertArrayNotHasKey('cache', $route->getOptions());
    }

    //! @brief Test dynamic path matching for article routes
    public function test_dynamic_path_matching_for_article_routes(): void
    {
        //! @section Arrange
        $readRoute = new Route('/read', TemplateName::ARTICLE);
        $articleRoute = new Route('/article', TemplateName::ARTICLE);
        $blogRoute = new Route('/blog', TemplateName::ARTICLE);

        //! @section Act - /read routes
        $readMatchesRead = $readRoute->matches('/read');
        $readMatchesReadWordRotator = $readRoute->matches('/read/word-rotator');
        $readMatchesReadTestArticle = $readRoute->matches('/read/test-article');
        $readMatchesReadWordRotatorExtra = $readRoute->matches('/read/word-rotator/extra');
        $readMatchesArticle = $readRoute->matches('/article');

        //! @section Assert - /read routes
        $this->assertTrue($readMatchesRead);
        $this->assertTrue($readMatchesReadWordRotator);
        $this->assertTrue($readMatchesReadTestArticle);
        $this->assertFalse($readMatchesReadWordRotatorExtra);
        $this->assertFalse($readMatchesArticle);

        //! @section Act - /article routes
        $articleMatchesArticle = $articleRoute->matches('/article');
        $articleMatchesArticleWordRotator = $articleRoute->matches('/article/word-rotator');
        $articleMatchesArticleTestArticle = $articleRoute->matches('/article/test-article');
        $articleMatchesArticleWordRotatorExtra = $articleRoute->matches('/article/word-rotator/extra');
        $articleMatchesRead = $articleRoute->matches('/read');

        //! @section Assert - /article routes
        $this->assertTrue($articleMatchesArticle);
        $this->assertTrue($articleMatchesArticleWordRotator);
        $this->assertTrue($articleMatchesArticleTestArticle);
        $this->assertFalse($articleMatchesArticleWordRotatorExtra);
        $this->assertFalse($articleMatchesRead);

        //! @section Act - /blog routes
        $blogMatchesBlog = $blogRoute->matches('/blog');
        $blogMatchesBlogWordRotator = $blogRoute->matches('/blog/word-rotator');
        $blogMatchesBlogTestArticle = $blogRoute->matches('/blog/test-article');
        $blogMatchesBlogWordRotatorExtra = $blogRoute->matches('/blog/word-rotator/extra');
        $blogMatchesRead = $blogRoute->matches('/read');

        //! @section Assert - /blog routes
        $this->assertTrue($blogMatchesBlog);
        $this->assertTrue($blogMatchesBlogWordRotator);
        $this->assertTrue($blogMatchesBlogTestArticle);
        $this->assertFalse($blogMatchesBlogWordRotatorExtra);
        $this->assertFalse($blogMatchesRead);
    }

    //! @brief Test parameter extraction from article routes
    public function test_parameter_extraction_from_article_routes(): void
    {
        //! @section Arrange
        $readRoute = new Route('/read', TemplateName::ARTICLE);
        $articleRoute = new Route('/article', TemplateName::ARTICLE);
        $blogRoute = new Route('/blog', TemplateName::ARTICLE);

        //! @section Act - /read routes
        $readParamsForRead = $readRoute->extractParameters('/read');
        $readParamsForReadWordRotator = $readRoute->extractParameters('/read/word-rotator');
        $readParamsForReadTestArticle = $readRoute->extractParameters('/read/test-article');
        $readParamsForRoot = $readRoute->extractParameters('/');

        //! @section Assert - /read routes
        $this->assertEquals([], $readParamsForRead);
        $this->assertEquals(['article_name' => 'word-rotator'], $readParamsForReadWordRotator);
        $this->assertEquals(['article_name' => 'test-article'], $readParamsForReadTestArticle);
        $this->assertEquals([], $readParamsForRoot);

        //! @section Act - /article routes
        $articleParamsForArticle = $articleRoute->extractParameters('/article');
        $articleParamsForArticleWordRotator = $articleRoute->extractParameters('/article/word-rotator');
        $articleParamsForArticleTestArticle = $articleRoute->extractParameters('/article/test-article');

        //! @section Assert - /article routes
        $this->assertEquals([], $articleParamsForArticle);
        $this->assertEquals(['article_name' => 'word-rotator'], $articleParamsForArticleWordRotator);
        $this->assertEquals(['article_name' => 'test-article'], $articleParamsForArticleTestArticle);

        //! @section Act - /blog routes
        $blogParamsForBlog = $blogRoute->extractParameters('/blog');
        $blogParamsForBlogWordRotator = $blogRoute->extractParameters('/blog/word-rotator');
        $blogParamsForBlogTestArticle = $blogRoute->extractParameters('/blog/test-article');

        //! @section Assert - /blog routes
        $this->assertEquals([], $blogParamsForBlog);
        $this->assertEquals(['article_name' => 'word-rotator'], $blogParamsForBlogWordRotator);
        $this->assertEquals(['article_name' => 'test-article'], $blogParamsForBlogTestArticle);
    }
}
