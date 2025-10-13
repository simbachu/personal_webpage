<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Presenter\DexPresenter;
use App\Service\PokeApiService;
use App\Type\Result;
use App\Type\MonsterData;
use App\Type\MonsterIdentifier;

final class DexPresenterTest extends TestCase
{
    public function test_present_builds_view_model_for_single_type_pokemon(): void
    {
        //! @section Arrange
        $monsterData = new MonsterData(
            id: 7,
            name: 'Squirtle',
            image: 'https://img.example/squirtle.png',
            type1: 'water'
        );

        $presenter = new DexPresenter($this->createMock(PokeApiService::class), 300);

        //! @section Act
        $view = $presenter->present($monsterData);

        //! @section Assert
        $this->assertArrayHasKey('monster', $view);
        $this->assertSame('Squirtle', $view['monster']['name']);
        $this->assertSame(7, $view['monster']['id']);
        $this->assertSame('water', $view['monster']['type1']);
        $this->assertSame('https://img.example/squirtle.png', $view['monster']['image']);
        $this->assertSame('dex', $view['template']);

        //! Ensure type2 is absent for single-type Pokemon
        $this->assertArrayNotHasKey('type2', $view['monster']);
    }

    public function test_present_builds_view_model_for_dual_type_pokemon(): void
    {
        //! @section Arrange
        $monsterData = new MonsterData(
            id: 1,
            name: 'Bulbasaur',
            image: 'https://img.example/bulbasaur.png',
            type1: 'grass',
            type2: 'poison'
        );

        $presenter = new DexPresenter($this->createMock(PokeApiService::class), 300);

        //! @section Act
        $view = $presenter->present($monsterData);

        //! @section Assert
        $this->assertArrayHasKey('monster', $view);
        $this->assertSame('Bulbasaur', $view['monster']['name']);
        $this->assertSame(1, $view['monster']['id']);
        $this->assertSame('grass', $view['monster']['type1']);
        $this->assertSame('poison', $view['monster']['type2']);
        $this->assertSame('https://img.example/bulbasaur.png', $view['monster']['image']);
        $this->assertSame('dex', $view['template']);

        //! Ensure type2 exists for dual-type Pokemon
        $this->assertArrayHasKey('type2', $view['monster']);
    }

    public function test_fetch_monster_data_uses_custom_cache_ttl(): void
    {
        //! @section Arrange
        $monsterData = new MonsterData(
            id: 25,
            name: 'Pikachu',
            image: 'https://img.example/pikachu.png',
            type1: 'electric'
        );

        $service = $this->createMock(PokeApiService::class);
        $service->expects($this->once())
            ->method('fetchMonster')
            ->with('25', null, 60) // Verify custom TTL is passed
            ->willReturn(Result::success($monsterData));

        $presenter = new DexPresenter($service, 60); // Custom 60-second TTL

        //! @section Act
        $fetchedData = $presenter->fetchMonsterData(MonsterIdentifier::fromString('25'));

        //! @section Assert
        $this->assertSame($monsterData, $fetchedData);
        $this->assertSame('Pikachu', $fetchedData->name);
    }

    public function test_fetch_monster_data_throws_on_service_failure(): void
    {
        //! @section Arrange
        $service = $this->createMock(PokeApiService::class);
        $service->method('fetchMonster')->willReturn(Result::failure('Pokemon not found'));

        $presenter = new DexPresenter($service, 300);

        //! @section Act & Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Pokemon not found');
        $presenter->fetchMonsterData(MonsterIdentifier::fromString('999'));
    }
}


