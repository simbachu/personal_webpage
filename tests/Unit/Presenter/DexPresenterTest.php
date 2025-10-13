<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Presenter\DexPresenter;
use App\Service\PokeApiService;

final class DexPresenterTest extends TestCase
{
    public function test_present_builds_view_model_for_single_type_pokemon(): void
    {
        //! @section Arrange
        $service = $this->createMock(PokeApiService::class);
        $service->method('fetchMonster')->with('7')->willReturn([
            'id' => 7,
            'name' => 'Squirtle',
            'image' => 'https://img.example/squirtle.png',
            'type1' => 'water',
        ]);

        $presenter = new DexPresenter($service);

        //! @section Act
        $view = $presenter->present('7');

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
        $service = $this->createMock(PokeApiService::class);
        $service->method('fetchMonster')->with('1')->willReturn([
            'id' => 1,
            'name' => 'Bulbasaur',
            'image' => 'https://img.example/bulbasaur.png',
            'type1' => 'grass',
            'type2' => 'poison',
        ]);

        $presenter = new DexPresenter($service);

        //! @section Act
        $view = $presenter->present('1');

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
}


