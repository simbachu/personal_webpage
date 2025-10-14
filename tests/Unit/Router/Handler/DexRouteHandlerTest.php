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

    //! @brief Test dex route handler generates comprehensive OG meta data for Pokemon with rating
    public function test_dex_route_handler_generates_comprehensive_og_meta_with_rating(): void
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
                'monster' => [
                    'id' => 25,
                    'name' => 'Pikachu',
                    'image' => 'https://example.com/pikachu.png',
                    'opinion' => 'My sister caught a wild Pikachu in Viridian Forest.',
                    'rating' => 'A'
                ]
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
        $this->assertArrayHasKey('meta', $data);

        $meta = $data['meta'];
        $this->assertEquals('Pikachu #25 - Jennifer\'s Rating: A', $meta['og_title']);
        $this->assertEquals('My sister caught a wild Pikachu in Viridian Forest.', $meta['og_description']);
        $this->assertEquals('https://example.com/pikachu.png', $meta['og_image']);
        $this->assertEquals('Pikachu - Pokemon #25', $meta['og_image_alt']);
        $this->assertEquals('Pikachu #25', $meta['title']);
        $this->assertEquals('Pokédex entry for Pikachu', $meta['description']);
    }

    //! @brief Test dex route handler generates OG meta data for Pokemon without rating
    public function test_dex_route_handler_generates_og_meta_without_rating(): void
    {
        //! @section Arrange
        $monsterData = new MonsterData(
            id: 999,
            name: 'Unknown Pokemon',
            image: 'https://example.com/unknown.png',
            type1: \App\Type\MonsterType::NORMAL
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
                'monster' => [
                    'id' => 999,
                    'name' => 'Unknown Pokemon',
                    'image' => 'https://example.com/unknown.png'
                    // No opinion or rating
                ]
            ]);

        $handler = new DexRouteHandler($presenter);
        $route = new Route('/dex', TemplateName::DEX);
        $parameters = ['id_or_name' => 'unknown'];

        //! @section Act
        $result = $handler->handle($route, $parameters);

        //! @section Assert
        $this->assertEquals(TemplateName::DEX, $result->getTemplate());
        $this->assertEquals(HttpStatusCode::OK, $result->getStatusCode());

        $data = $result->getData();
        $this->assertArrayHasKey('meta', $data);

        $meta = $data['meta'];
        $this->assertEquals('Unknown Pokemon #999', $meta['og_title']);
        $this->assertEquals('Pokédex entry for Unknown Pokemon', $meta['og_description']);
        $this->assertEquals('https://example.com/unknown.png', $meta['og_image']);
        $this->assertEquals('Unknown Pokemon - Pokemon #999', $meta['og_image_alt']);
        $this->assertEquals('Unknown Pokemon #999', $meta['title']);
        $this->assertEquals('Pokédex entry for Unknown Pokemon', $meta['description']);
    }

    //! @brief Test dex route handler handles Pokemon without opinion or rating
    public function test_dex_route_handler_handles_pokemon_without_opinion_or_rating(): void
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
                'monster' => [
                    'id' => 25,
                    'name' => 'Pikachu',
                    'image' => 'https://example.com/pikachu.png'
                    // No opinion or rating
                ]
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
        $this->assertArrayHasKey('meta', $data);

        $meta = $data['meta'];
        $this->assertEquals('Pikachu #25', $meta['og_title']);
        $this->assertEquals('Pokédex entry for Pikachu', $meta['og_description']);
        $this->assertEquals('https://example.com/pikachu.png', $meta['og_image']);
        $this->assertEquals('Pikachu - Pokemon #25', $meta['og_image_alt']);
        $this->assertEquals('Pikachu #25', $meta['title']);
        $this->assertEquals('Pokédex entry for Pikachu', $meta['description']);
    }

    //! @brief Test dex route handler truncates long opinions in OG description
    public function test_dex_route_handler_truncates_long_opinions_in_og_description(): void
    {
        //! @section Arrange
        $monsterData = new MonsterData(
            id: 25,
            name: 'Pikachu',
            image: 'https://example.com/pikachu.png',
            type1: \App\Type\MonsterType::ELECTRIC
        );

        $longOpinion = 'This is a very long opinion about Pikachu that should be truncated because it exceeds the maximum length allowed for Open Graph descriptions. It goes on and on with many words that will definitely exceed the character limit we want to enforce.';

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
                'monster' => [
                    'id' => 25,
                    'name' => 'Pikachu',
                    'image' => 'https://example.com/pikachu.png',
                    'opinion' => $longOpinion,
                    'rating' => 'A'
                ]
            ]);

        $handler = new DexRouteHandler($presenter);
        $route = new Route('/dex', TemplateName::DEX);
        $parameters = ['id_or_name' => 'pikachu'];

        //! @section Act
        $result = $handler->handle($route, $parameters);

        //! @section Assert
        $this->assertEquals(TemplateName::DEX, $result->getTemplate());

        $data = $result->getData();
        $meta = $data['meta'];

        $this->assertEquals('Pikachu #25 - Jennifer\'s Rating: A', $meta['og_title']);

        // Should truncate the opinion to 100 characters + "..."
        $expectedDescription = 'This is a very long opinion about Pikachu that should be truncated because it exceeds the maximum le...';
        $this->assertEquals($expectedDescription, $meta['og_description']);
        $this->assertLessThanOrEqual(150, strlen($meta['og_description'])); // Reasonable limit
    }
}
