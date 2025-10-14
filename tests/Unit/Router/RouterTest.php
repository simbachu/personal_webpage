<?php

declare(strict_types=1);

namespace Tests\Unit\Router;

use PHPUnit\Framework\TestCase;
use App\Router\Router;
use App\Router\RouteHandler;
use App\Router\RouteResult;
use App\Type\Route;
use App\Type\TemplateName;
use App\Type\HttpStatusCode;

//! @brief Unit tests for Router class
class RouterTest extends TestCase
{
    private Router $router;

    //! @brief Set up test environment before each test
    protected function setUp(): void
    {
        $this->router = new Router();
    }

    //! @brief Test adding routes to router
    public function test_can_add_routes_to_router(): void
    {
        //! @section Arrange
        $route1 = new Route('/', TemplateName::HOME);
        $route2 = new Route('/about', TemplateName::HOME);

        //! @section Act
        $this->router->addRoute($route1);
        $this->router->addRoute($route2);

        //! @section Assert
        $routes = $this->router->getRoutes();
        $this->assertCount(2, $routes);
        $this->assertEquals('/', $routes[0]->getPath());
        $this->assertEquals('/about', $routes[1]->getPath());
    }

    //! @brief Test registering route handlers
    public function test_can_register_route_handlers(): void
    {
        //! @section Arrange
        $handler = $this->createMock(RouteHandler::class);

        //! @section Act
        $this->router->registerHandler('test', $handler);

        //! @section Assert
        $handlers = $this->router->getHandlers();
        $this->assertArrayHasKey('test', $handlers);
        $this->assertSame($handler, $handlers['test']);
    }

    //! @brief Test routing to static route without handler
    public function test_routes_to_static_route_without_handler(): void
    {
        //! @section Arrange
        $meta = ['title' => 'Test Page'];
        $route = new Route('/test', TemplateName::HOME, $meta);
        $this->router->addRoute($route);

        //! @section Act
        $result = $this->router->route('/test');

        //! @section Assert
        $this->assertEquals(TemplateName::HOME, $result->getTemplate());
        $this->assertEquals(HttpStatusCode::OK, $result->getStatusCode());
        $this->assertArrayHasKey('meta', $result->getData());
        $this->assertEquals($meta, $result->getData()['meta']);
    }

    //! @brief Test routing to static route with handler
    public function test_routes_to_static_route_with_handler(): void
    {
        //! @section Arrange
        $handler = $this->createMock(RouteHandler::class);
        $handlerResult = new RouteResult(TemplateName::HOME, ['data' => 'test']);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturn($handlerResult);

        $route = new Route('/test', TemplateName::HOME, [], ['handler' => 'test']);
        $this->router->addRoute($route);
        $this->router->registerHandler('test', $handler);

        //! @section Act
        $result = $this->router->route('/test');

        //! @section Assert
        $this->assertEquals(TemplateName::HOME, $result->getTemplate());
        $this->assertEquals(HttpStatusCode::OK, $result->getStatusCode());
        $this->assertEquals('test', $result->getData()['data']);
        $this->assertArrayHasKey('meta', $result->getData());
    }

    //! @brief Test routing to dynamic route
    public function test_routes_to_dynamic_route(): void
    {
        //! @section Arrange
        $handler = $this->createMock(RouteHandler::class);
        $handlerResult = new RouteResult(TemplateName::DEX, ['monster' => 'pikachu']);
        $handler->expects($this->once())
            ->method('handle')
            ->with($this->isInstanceOf(Route::class), ['id_or_name' => 'pikachu'])
            ->willReturn($handlerResult);

        $route = new Route('/dex', TemplateName::DEX, [], ['handler' => 'dex']);
        $this->router->addRoute($route);
        $this->router->registerHandler('dex', $handler);

        //! @section Act
        $result = $this->router->route('/dex/pikachu');

        //! @section Assert
        $this->assertEquals(TemplateName::DEX, $result->getTemplate());
        $this->assertEquals(HttpStatusCode::OK, $result->getStatusCode());
        $this->assertEquals('pikachu', $result->getData()['monster']);
    }

    //! @brief Test routing to non-existent route returns 404
    public function test_routing_to_nonexistent_route_returns_404(): void
    {
        //! @section Act
        $result = $this->router->route('/nonexistent');

        //! @section Assert
        $this->assertEquals(TemplateName::NOT_FOUND, $result->getTemplate());
        $this->assertEquals(HttpStatusCode::NOT_FOUND, $result->getStatusCode());
        $this->assertArrayHasKey('meta', $result->getData());
        $this->assertEquals('Page Not Found', $result->getData()['meta']['title']);
    }

    //! @brief Test path normalization in routing
    public function test_path_normalization_in_routing(): void
    {
        //! @section Arrange
        $route = new Route('/test', TemplateName::HOME);
        $this->router->addRoute($route);

        //! @section Act & Assert
        $result1 = $this->router->route('/test');
        $result2 = $this->router->route('/test/');

        $this->assertEquals(TemplateName::HOME, $result1->getTemplate());
        $this->assertEquals(TemplateName::HOME, $result2->getTemplate());
    }

    //! @brief Test root path routing
    public function test_root_path_routing(): void
    {
        //! @section Arrange
        $route = new Route('/', TemplateName::HOME);
        $this->router->addRoute($route);

        //! @section Act
        $result = $this->router->route('/');

        //! @section Assert
        $this->assertEquals(TemplateName::HOME, $result->getTemplate());
        $this->assertEquals(HttpStatusCode::OK, $result->getStatusCode());
    }

    //! @brief Test route priority (first match wins)
    public function test_route_priority_first_match_wins(): void
    {
        //! @section Arrange
        $route1 = new Route('/test', TemplateName::HOME, ['title' => 'First']);
        $route2 = new Route('/test', TemplateName::DEX, ['title' => 'Second']);
        $this->router->addRoute($route1);
        $this->router->addRoute($route2);

        //! @section Act
        $result = $this->router->route('/test');

        //! @section Assert
        $this->assertEquals(TemplateName::HOME, $result->getTemplate());
        $this->assertEquals('First', $result->getData()['meta']['title']);
    }

    //! @brief Test clearing routes and handlers
    public function test_can_clear_routes_and_handlers(): void
    {
        //! @section Arrange
        $route = new Route('/test', TemplateName::HOME);
        $handler = $this->createMock(RouteHandler::class);
        $this->router->addRoute($route);
        $this->router->registerHandler('test', $handler);

        //! @section Act
        $this->router->clear();

        //! @section Assert
        $this->assertEmpty($this->router->getRoutes());
        $this->assertEmpty($this->router->getHandlers());
    }

    //! @brief Test routing with handler that returns non-200 status
    public function test_routing_with_handler_returning_non_200_status(): void
    {
        //! @section Arrange
        $handler = $this->createMock(RouteHandler::class);
        $handlerResult = new RouteResult(TemplateName::NOT_FOUND, ['error' => 'Not found'], HttpStatusCode::NOT_FOUND);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturn($handlerResult);

        $route = new Route('/test', TemplateName::HOME, [], ['handler' => 'test']);
        $this->router->addRoute($route);
        $this->router->registerHandler('test', $handler);

        //! @section Act
        $result = $this->router->route('/test');

        //! @section Assert
        $this->assertEquals(TemplateName::NOT_FOUND, $result->getTemplate());
        $this->assertEquals(HttpStatusCode::NOT_FOUND, $result->getStatusCode());
        $this->assertEquals('Not found', $result->getData()['error']);
    }
}
