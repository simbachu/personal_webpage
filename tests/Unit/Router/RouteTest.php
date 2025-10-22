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

        //! @section Act & Assert
        $this->assertTrue($route->matches('/about'));
        $this->assertTrue($route->matches('/about/'));
        $this->assertFalse($route->matches('/'));
        $this->assertFalse($route->matches('/about/extra'));
    }

    //! @brief Test dynamic path matching for dex routes
    public function test_dynamic_path_matching_for_dex(): void
    {
        //! @section Arrange
        $route = new Route('/dex', TemplateName::DEX);

        //! @section Act & Assert
        $this->assertTrue($route->matches('/dex'));
        $this->assertTrue($route->matches('/dex/pikachu'));
        $this->assertTrue($route->matches('/dex/25'));
        $this->assertFalse($route->matches('/dex/pikachu/extra'));
        $this->assertFalse($route->matches('/pokemon'));
    }

    //! @brief Test parameter extraction from dynamic routes
    public function test_parameter_extraction_from_dynamic_routes(): void
    {
        //! @section Arrange
        $route = new Route('/dex', TemplateName::DEX);

        //! @section Act & Assert
        $this->assertEquals([], $route->extractParameters('/dex'));
        $this->assertEquals(['id_or_name' => 'pikachu'], $route->extractParameters('/dex/pikachu'));
        $this->assertEquals(['id_or_name' => '25'], $route->extractParameters('/dex/25'));
        $this->assertEquals([], $route->extractParameters('/'));
    }

    //! @brief Test metadata access methods
    public function test_metadata_access_methods(): void
    {
        //! @section Arrange
        $meta = ['title' => 'Test Title', 'description' => 'Test Description'];
        $route = new Route('/', TemplateName::HOME, $meta);

        //! @section Act & Assert
        $this->assertEquals('Test Title', $route->getMetaValue('title'));
        $this->assertEquals('Test Description', $route->getMetaValue('description'));
        $this->assertNull($route->getMetaValue('nonexistent'));
        $this->assertEquals('default', $route->getMetaValue('nonexistent', 'default'));
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

        //! @section Act & Assert - /read routes
        $this->assertTrue($readRoute->matches('/read'));
        $this->assertTrue($readRoute->matches('/read/word-rotator'));
        $this->assertTrue($readRoute->matches('/read/test-article'));
        $this->assertFalse($readRoute->matches('/read/word-rotator/extra'));
        $this->assertFalse($readRoute->matches('/article'));

        //! @section Act & Assert - /article routes
        $this->assertTrue($articleRoute->matches('/article'));
        $this->assertTrue($articleRoute->matches('/article/word-rotator'));
        $this->assertTrue($articleRoute->matches('/article/test-article'));
        $this->assertFalse($articleRoute->matches('/article/word-rotator/extra'));
        $this->assertFalse($articleRoute->matches('/read'));

        //! @section Act & Assert - /blog routes
        $this->assertTrue($blogRoute->matches('/blog'));
        $this->assertTrue($blogRoute->matches('/blog/word-rotator'));
        $this->assertTrue($blogRoute->matches('/blog/test-article'));
        $this->assertFalse($blogRoute->matches('/blog/word-rotator/extra'));
        $this->assertFalse($blogRoute->matches('/read'));
    }

    //! @brief Test parameter extraction from article routes
    public function test_parameter_extraction_from_article_routes(): void
    {
        //! @section Arrange
        $readRoute = new Route('/read', TemplateName::ARTICLE);
        $articleRoute = new Route('/article', TemplateName::ARTICLE);
        $blogRoute = new Route('/blog', TemplateName::ARTICLE);

        //! @section Act & Assert - /read routes
        $this->assertEquals([], $readRoute->extractParameters('/read'));
        $this->assertEquals(['article_name' => 'word-rotator'], $readRoute->extractParameters('/read/word-rotator'));
        $this->assertEquals(['article_name' => 'test-article'], $readRoute->extractParameters('/read/test-article'));
        $this->assertEquals([], $readRoute->extractParameters('/'));

        //! @section Act & Assert - /article routes
        $this->assertEquals([], $articleRoute->extractParameters('/article'));
        $this->assertEquals(['article_name' => 'word-rotator'], $articleRoute->extractParameters('/article/word-rotator'));
        $this->assertEquals(['article_name' => 'test-article'], $articleRoute->extractParameters('/article/test-article'));

        //! @section Act & Assert - /blog routes
        $this->assertEquals([], $blogRoute->extractParameters('/blog'));
        $this->assertEquals(['article_name' => 'word-rotator'], $blogRoute->extractParameters('/blog/word-rotator'));
        $this->assertEquals(['article_name' => 'test-article'], $blogRoute->extractParameters('/blog/test-article'));
    }
}
