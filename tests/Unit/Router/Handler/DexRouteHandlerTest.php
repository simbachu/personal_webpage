<?php

declare(strict_types=1);

namespace Tests\Unit\Router\Handler;

use PHPUnit\Framework\TestCase;
use App\Router\Handler\DexRouteHandler;
use App\Type\Route;
use App\Type\TemplateName;
use App\Type\MonsterIdentifier;
use App\Type\MonsterData;
use App\Type\HttpStatusCode;
use App\Presenter\DexPresenter;

//! @brief Unit tests for DexRouteHandler
class DexRouteHandlerTest extends TestCase
{
    //! @brief Test dex route handler processes /dex route correctly
    public function test_dex_route_handler_processes_dex_route_correctly(): void
    {
        //! @section Arrange
        $presenter = $this->createMock(DexPresenter::class);
        $handler = new DexRouteHandler($presenter);
        $route = new Route('/dex', TemplateName::DEX);

        //! @section Act
        $result = $handler->handle($route);

        //! @section Assert
        $this->assertEquals(TemplateName::DEX, $result->getTemplate());
        $this->assertEquals(HttpStatusCode::OK, $result->getStatusCode());
        $this->assertEmpty($result->getData());
    }

    //! @brief Test dex route handler processes /dex/{id} route correctly
    public function test_dex_route_handler_processes_dex_id_route_correctly(): void
    {
        //! @section Arrange
        $monsterData = new MonsterData(
            id: 25,
            name: 'Pikachu',
            image: 'https://example.com/pikachu.png',
            type1: \App\Type\MonsterType::ELECTRIC
        );

        $presenter = $this->createMock(DexPresenter::class);
        $presenter->expects($this->once())
            ->method('fetchMonsterData')
            ->with($this->isInstanceOf(MonsterIdentifier::class))
            ->willReturn($monsterData);
        $presenter->expects($this->once())
            ->method('present')
            ->with($monsterData)
            ->willReturn([
                'template' => TemplateName::DEX,
                'monster' => ['id' => 25, 'name' => 'Pikachu']
            ]);

        $handler = new DexRouteHandler($presenter);
        $route = new Route('/dex', TemplateName::DEX);
        $parameters = ['id_or_name' => 'pikachu'];

        //! @section Act
        $result = $handler->handle($route, $parameters);

        //! @section Assert
        $this->assertEquals(TemplateName::DEX, $result->getTemplate());
        $this->assertEquals(HttpStatusCode::OK, $result->getStatusCode());
        $data = $result->getData();
        $this->assertArrayHasKey('monster', $data);
        $this->assertEquals(25, $data['monster']['id']);
        $this->assertEquals('Pikachu', $data['monster']['name']);
        $this->assertArrayHasKey('meta', $data);
        $this->assertEquals('Pikachu #25', $data['meta']['title']);
    }

    //! @brief Test dex route handler handles empty id_or_name parameter
    public function test_dex_route_handler_handles_empty_id_or_name(): void
    {
        //! @section Arrange
        $presenter = $this->createMock(DexPresenter::class);
        $handler = new DexRouteHandler($presenter);
        $route = new Route('/dex', TemplateName::DEX);
        $parameters = ['id_or_name' => ''];

        //! @section Act
        $result = $handler->handle($route, $parameters);

        //! @section Assert
        $this->assertEquals(TemplateName::NOT_FOUND, $result->getTemplate());
        $this->assertEquals(HttpStatusCode::BAD_REQUEST, $result->getStatusCode());
        $data = $result->getData();
        $this->assertArrayHasKey('meta', $data);
        $this->assertEquals('Invalid Pokédex Request', $data['meta']['title']);
        $this->assertEquals('No Pokémon specified.', $data['meta']['description']);
    }

    //! @brief Test dex route handler handles Pokemon fetch failure
    public function test_dex_route_handler_handles_pokemon_fetch_failure(): void
    {
        //! @section Arrange
        $presenter = $this->createMock(DexPresenter::class);
        $presenter->expects($this->once())
            ->method('fetchMonsterData')
            ->with($this->isInstanceOf(MonsterIdentifier::class))
            ->willThrowException(new \RuntimeException('Pokemon not found'));

        $handler = new DexRouteHandler($presenter);
        $route = new Route('/dex', TemplateName::DEX);
        $parameters = ['id_or_name' => 'nonexistent'];

        //! @section Act
        $result = $handler->handle($route, $parameters);

        //! @section Assert
        $this->assertEquals(TemplateName::NOT_FOUND, $result->getTemplate());
        $this->assertEquals(HttpStatusCode::NOT_FOUND, $result->getStatusCode());
        $data = $result->getData();
        $this->assertArrayHasKey('meta', $data);
        $this->assertEquals('Pokémon Not Found', $data['meta']['title']);
        $this->assertEquals('Pokemon not found', $data['meta']['description']);
    }

    //! @brief Test dex route handler ignores unused parameters
    public function test_dex_route_handler_ignores_unused_parameters(): void
    {
        //! @section Arrange
        $presenter = $this->createMock(DexPresenter::class);
        $handler = new DexRouteHandler($presenter);
        $route = new Route('/dex', TemplateName::DEX);
        $parameters = ['unused' => 'parameter'];

        //! @section Act
        $result = $handler->handle($route, $parameters);

        //! @section Assert
        // When there are unused parameters (no id_or_name), it should return DEX template for /dex route
        $this->assertEquals(TemplateName::DEX, $result->getTemplate());
        $this->assertEquals(HttpStatusCode::OK, $result->getStatusCode());
    }
}
