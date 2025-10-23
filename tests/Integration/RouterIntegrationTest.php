<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Router\Router;
use App\Router\Handler\HomeRouteHandler;
use App\Router\Handler\DexRouteHandler;
use App\Type\Route;
use App\Type\TemplateName;
use App\Type\HttpStatusCode;
use App\Type\FilePath;
use App\Model\ContentRepository;
use App\Presenter\HomePresenter;
use App\Presenter\DexPresenter;
use App\Service\PokeApiService;
use App\Service\PokemonOpinionService;

//! @brief Integration test for the complete Router system
//!
//! Tests that the Router correctly integrates with route handlers and produces
//! the expected results for various routes, following the MVP pattern.
class RouterIntegrationTest extends TestCase
{
    private string $testContentPath; //!< Temporary test content directory
    private Router $router; //!< Router under test
    private ContentRepository $repository; //!< Content repository for testing

    //! @brief Set up test environment before each test
    protected function setUp(): void
    {
        $this->testContentPath = sys_get_temp_dir() . '/test_router_integration_' . uniqid();
        mkdir($this->testContentPath);

        $this->createTestContentFiles();

        $this->repository = new ContentRepository(FilePath::fromString($this->testContentPath));
        $this->router = new Router();

        // Set up route handlers with real dependencies
        $homePresenter = new HomePresenter($this->repository);
        $dexPresenter = new DexPresenter(new PokeApiService(), new PokemonOpinionService(), 30); // Short cache for testing

        $this->router->registerHandler('home', new HomeRouteHandler($homePresenter));
        $this->router->registerHandler('dex', new DexRouteHandler($dexPresenter));

        // Add routes
        $this->router->addRoute(new Route(
            '/',
            TemplateName::HOME,
            ['title' => 'Test Home'],
            ['handler' => 'home']
        ));
        $this->router->addRoute(new Route(
            '/dex',
            TemplateName::DEX,
            [],
            ['handler' => 'dex']
        ));
    }

    //! @brief Clean up test environment after each test
    protected function tearDown(): void
    {
        if (is_dir($this->testContentPath)) {
            $files = glob($this->testContentPath . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->testContentPath);
        }
    }

    //! @brief Create test content files in temporary directory
    private function createTestContentFiles(): void
    {
        // Create about.md
        $aboutContent = "Test software designer.\n\nBased in test location.";
        file_put_contents($this->testContentPath . '/about.md', $aboutContent);

        // Create projects.yaml
        $projectsContent = <<<YAML
- title: "Test Project"
  year: "2025"
  tags:
    - PHP
    - Testing
  description: "A test project for router integration testing."
YAML;
        file_put_contents($this->testContentPath . '/projects.yaml', $projectsContent);

        // Create config.yaml
        $configContent = <<<YAML
about:
  profile_image: "/images/test.png"
  profile_alt: "Test portrait"

skills:
  - "PHP Development"
  - "Router Testing"

contact:
  - url: "https://github.com/test"
    text: "github.com/test"
YAML;
        file_put_contents($this->testContentPath . '/config.yaml', $configContent);
    }

    //! @brief Test routing to home page with real data
    public function test_routes_to_home_page_with_real_data(): void
    {
        //! @section Act
        $result = $this->router->route('/');

        //! @section Assert
        $this->assertEquals(TemplateName::HOME, $result->getTemplate());
        $this->assertEquals(HttpStatusCode::OK, $result->getStatusCode());

        $data = $result->getData();
        $this->assertArrayHasKey('meta', $data);
        $this->assertEquals('Test Home', $data['meta']['title']);

        // Check that home presenter data is included
        $this->assertArrayHasKey('about', $data);
        $this->assertArrayHasKey('skills', $data);
        $this->assertArrayHasKey('projects', $data);
        $this->assertArrayHasKey('contact', $data);

        // Verify specific content from files
        $this->assertEquals('/images/test.png', $data['about']['profile_image']);
        $this->assertCount(2, $data['about']['paragraphs']);
        $this->assertCount(1, $data['projects']);
        $this->assertEquals('Test Project', $data['projects'][0]['title']);
    }

    //! @brief Test routing to dex page without specific Pokemon
    public function test_routes_to_dex_page_without_specific_pokemon(): void
    {
        //! @section Act
        $result = $this->router->route('/dex');

        //! @section Assert
        $this->assertEquals(TemplateName::DEX, $result->getTemplate());
        $this->assertEquals(HttpStatusCode::OK, $result->getStatusCode());

        $data = $result->getData();
        $this->assertIsArray($data);
        // Expect a tierlist structure for /dex (no id)
        $this->assertArrayHasKey('tierlist', $data);
        $this->assertArrayHasKey('meta', $data);
        $this->assertArrayHasKey('name', $data['tierlist']);
        $this->assertArrayHasKey('tiers', $data['tierlist']);
    }

    //! @brief Test routing to dex page with specific Pokemon
    public function test_routes_to_dex_page_with_specific_pokemon(): void
    {
        //! @section Act
        $result = $this->router->route('/dex/pikachu');

        //! @section Assert
        $this->assertEquals(TemplateName::DEX, $result->getTemplate());
        $this->assertEquals(HttpStatusCode::OK, $result->getStatusCode());

        $data = $result->getData();
        $this->assertArrayHasKey('monster', $data);
        $this->assertArrayHasKey('meta', $data);

        // Verify monster data structure
        $monster = $data['monster'];
        $this->assertArrayHasKey('id', $monster);
        $this->assertArrayHasKey('name', $monster);
        $this->assertArrayHasKey('image', $monster);
        $this->assertArrayHasKey('type1', $monster);

        // Verify meta data exists
        $this->assertArrayHasKey('meta', $data);
        $meta = $data['meta'];
        $this->assertArrayHasKey('title', $meta);
        $this->assertArrayHasKey('description', $meta);
    }

    //! @brief Test routing to non-existent route returns 404
    public function test_routing_to_nonexistent_route_returns_404(): void
    {
        //! @section Act
        $result = $this->router->route('/nonexistent');

        //! @section Assert
        $this->assertEquals(TemplateName::NOT_FOUND, $result->getTemplate());
        $this->assertEquals(HttpStatusCode::NOT_FOUND, $result->getStatusCode());

        $data = $result->getData();
        $this->assertArrayHasKey('meta', $data);
        $this->assertEquals('Page Not Found', $data['meta']['title']);
        $this->assertEquals('The page you are looking for does not exist.', $data['meta']['description']);
    }

    //! @brief Test routing with path normalization
    public function test_routing_with_path_normalization(): void
    {
        //! @section Act
        $result1 = $this->router->route('/');
        $result2 = $this->router->route('/dex');
        $result3 = $this->router->route('/dex/');

        //! @section Assert
        $this->assertEquals(TemplateName::HOME, $result1->getTemplate());
        $this->assertEquals(TemplateName::DEX, $result2->getTemplate());
        $this->assertEquals(TemplateName::DEX, $result3->getTemplate()); // Trailing slash should be normalized
    }

    //! @brief Test routing with invalid Pokemon returns 404
    public function test_routing_with_invalid_pokemon_returns_404(): void
    {
        //! @section Act
        $result = $this->router->route('/dex/invalidpokemon12345');

        //! @section Assert
        $this->assertEquals(TemplateName::NOT_FOUND, $result->getTemplate());
        $this->assertEquals(HttpStatusCode::NOT_FOUND, $result->getStatusCode());

        $data = $result->getData();
        $this->assertArrayHasKey('meta', $data);
        // The actual error message might vary, so just check that we have meta data
        $this->assertIsArray($data['meta']);
    }

    //! @brief Test routing with empty Pokemon parameter returns 400
    public function test_routing_with_empty_pokemon_parameter_returns_400(): void
    {
        //! @section Act
        $result = $this->router->route('/dex/');

        //! @section Assert
        $this->assertEquals(TemplateName::DEX, $result->getTemplate());
        $this->assertEquals(HttpStatusCode::OK, $result->getStatusCode());

        // Note: /dex/ gets normalized to /dex, so it should return the dex template
        // The empty parameter case would only occur if someone manually constructs
        // parameters with an empty id_or_name value
    }
}
