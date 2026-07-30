<?php

declare(strict_types=1);

namespace Tests\Unit\Cv;

use App\Cv\CvLoader;
use App\Cv\CvRouteHandler;
use App\Shared\Http\HttpStatusCode;
use App\Shared\Http\Route;
use App\Shared\Http\TemplateName;
use PHPUnit\Framework\TestCase;
use Tests\Support\CvFixture;

//! @brief Unit tests for CvRouteHandler
final class CvRouteHandlerTest extends TestCase
{
    //! @brief Handler loads English CV and returns template data
    public function test_cv_route_handler_returns_cv_data(): void
    {
        //! @section Arrange
        $loader = CvLoader::fromString(CvFixture::path());
        $handler = new CvRouteHandler($loader);
        $route = new Route('/cv', TemplateName::CV);

        //! @section Act
        $result = $handler->handle($route);

        //! @section Assert
        $this->assertEquals(TemplateName::CV, $result->getTemplate());
        $this->assertEquals(HttpStatusCode::OK, $result->getStatusCode());

        $data = $result->getData();
        $this->assertArrayHasKey('cv', $data);
        $this->assertArrayHasKey('meta', $data);
        $this->assertSame(CvFixture::cv('en'), $data['cv']);
        $this->assertSame($data['cv']['name'] . ' — CV', $data['meta']['title']);
        $this->assertSame($data['cv']['summary'], $data['meta']['description']);
    }

    //! @brief Handler ignores unused route parameters
    public function test_cv_route_handler_ignores_parameters(): void
    {
        //! @section Arrange
        $loader = CvLoader::fromString(CvFixture::path());
        $handler = new CvRouteHandler($loader);
        $route = new Route('/cv', TemplateName::CV);

        //! @section Act
        $result = $handler->handle($route, ['unused' => 'parameter']);

        //! @section Assert
        $this->assertEquals(TemplateName::CV, $result->getTemplate());
        $this->assertEquals(HttpStatusCode::OK, $result->getStatusCode());
        $this->assertArrayHasKey('cv', $result->getData());
    }
}
