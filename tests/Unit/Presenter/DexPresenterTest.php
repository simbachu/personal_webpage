<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Presenter\DexPresenter;
use App\Service\PokeApiService;

final class DexPresenterTest extends TestCase
{
    public function test_present_builds_view_model_for_card(): void
    {
        // Arrange
        $service = $this->createMock(PokeApiService::class);
        $service->method('fetchPokemon')->with('7')->willReturn([
            'id' => 7,
            'name' => 'Squirtle',
            'image' => 'https://img.example/squirtle.png',
            'type1' => 'water',
        ]);

        $presenter = new DexPresenter($service);

        // Act
        $view = $presenter->present('7');

        // Assert
        $this->assertArrayHasKey('monster', $view);
        $this->assertSame('Squirtle', $view['monster']['name']);
        $this->assertSame(7, $view['monster']['id']);
        $this->assertSame('water', $view['monster']['type1']);
        $this->assertSame('https://img.example/squirtle.png', $view['monster']['image']);
        $this->assertSame('dex', $view['template']);
    }
}


