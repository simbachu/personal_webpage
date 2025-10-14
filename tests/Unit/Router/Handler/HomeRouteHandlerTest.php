<?php

declare(strict_types=1);

namespace Tests\Unit\Router\Handler;

use PHPUnit\Framework\TestCase;
use App\Router\Handler\HomeRouteHandler;
use App\Type\Route;
use App\Type\TemplateName;
use App\Presenter\HomePresenter;

//! @brief Unit tests for HomeRouteHandler
class HomeRouteHandlerTest extends TestCase
{
    //! @brief Test home route handler processes route correctly
    public function test_home_route_handler_processes_route_correctly(): void
    {
        //! @section Arrange
        $presenter = $this->createMock(HomePresenter::class);
        $presenter->expects($this->once())
            ->method('present')
            ->willReturn([
                'about' => ['profile_image' => '/test.png'],
                'skills' => ['PHP'],
                'projects' => [],
                'contact' => ['links' => []]
            ]);

        $handler = new HomeRouteHandler($presenter);
        $route = new Route('/', TemplateName::HOME);

        //! @section Act
        $result = $handler->handle($route);

        //! @section Assert
        $this->assertEquals(TemplateName::HOME, $result->getTemplate());
        $this->assertEquals(200, $result->getStatusCode());
        $data = $result->getData();
        $this->assertArrayHasKey('about', $data);
        $this->assertArrayHasKey('skills', $data);
        $this->assertArrayHasKey('projects', $data);
        $this->assertArrayHasKey('contact', $data);
        $this->assertEquals('/test.png', $data['about']['profile_image']);
    }

    //! @brief Test home route handler ignores parameters
    public function test_home_route_handler_ignores_parameters(): void
    {
        //! @section Arrange
        $presenter = $this->createMock(HomePresenter::class);
        $presenter->expects($this->once())
            ->method('present')
            ->willReturn([]);

        $handler = new HomeRouteHandler($presenter);
        $route = new Route('/', TemplateName::HOME);
        $parameters = ['unused' => 'parameter'];

        //! @section Act
        $result = $handler->handle($route, $parameters);

        //! @section Assert
        $this->assertEquals(TemplateName::HOME, $result->getTemplate());
        $this->assertEquals(200, $result->getStatusCode());
    }
}
