<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Presenter\DexPresenter;
use App\Service\PokeApiService;
use App\Service\PokemonOpinionService;
use App\Type\Result;
use App\Type\MonsterData;
use App\Type\MonsterIdentifier;
use App\Type\TemplateName;
use App\Type\MonsterType;

final class DexPresenterTest extends TestCase
{
    public function test_present_tierlist_groups_by_rating_and_includes_sprite_and_link(): void
    {
        //! @section Arrange
        $pokeApiService = $this->createMock(PokeApiService::class);

        // For three pokemon, return MonsterData with image used as sprite placeholder
        $pokeApiService->method('fetchMonster')->willReturnOnConsecutiveCalls(
            Result::success(new MonsterData(id: 133, name: 'Eevee', image: 'https://img.example/eevee-sprite.png', type1: MonsterType::NORMAL)),
            Result::success(new MonsterData(id: 172, name: 'Pichu', image: 'https://img.example/pichu-sprite.png', type1: MonsterType::ELECTRIC)),
            Result::success(new MonsterData(id: 25, name: 'Pikachu', image: 'https://img.example/pikachu-sprite.png', type1: MonsterType::ELECTRIC))
        );

        $opinionService = $this->createMock(PokemonOpinionService::class);
        $opinionService->method('getAllOpinionNames')->willReturn(['eevee', 'pichu', 'pikachu']);
        $opinionService->method('getOpinion')->willReturnOnConsecutiveCalls(
            Result::success(['opinion' => 'cute', 'rating' => 'A']),
            Result::success(['opinion' => 'ok', 'rating' => 'B']),
            Result::success(['opinion' => 'iconic', 'rating' => 'A'])
        );

        $presenter = new DexPresenter($pokeApiService, $opinionService, 300);

        //! @section Act
        $tierlist = $presenter->presentTierList();

        //! @section Assert
        $this->assertArrayHasKey('name', $tierlist);
        $this->assertArrayHasKey('tiers', $tierlist);
        $tiersByName = [];
        foreach ($tierlist['tiers'] as $tier) {
            $tiersByName[$tier['name']] = $tier;
        }
        $this->assertArrayHasKey('A', $tiersByName);
        $this->assertArrayHasKey('B', $tiersByName);

        // A tier should contain Eevee and Pikachu
        $aMonsters = array_column($tiersByName['A']['monsters'], 'name');
        $this->assertContains('Eevee', $aMonsters);
        $this->assertContains('Pikachu', $aMonsters);

        // Each monster should have sprite_image and url
        foreach ($tierlist['tiers'] as $tier) {
            foreach ($tier['monsters'] as $monster) {
                $this->assertArrayHasKey('sprite_image', $monster);
                $this->assertArrayHasKey('url', $monster);
                $this->assertStringStartsWith('/dex/', $monster['url']);
            }
        }
    }

    public function test_present_builds_view_model_for_single_type_pokemon(): void
    {
        //! @section Arrange
        $monsterData = new MonsterData(
            id: 7,
            name: 'Squirtle',
            image: 'https://img.example/squirtle.png',
            type1: MonsterType::WATER
        );

        $opinionService = $this->createMock(PokemonOpinionService::class);
        $opinionService->method('getOpinion')->willReturn(Result::failure('No opinion'));

        $presenter = new DexPresenter($this->createMock(PokeApiService::class), $opinionService, 300);

        //! @section Act
        $view = $presenter->present($monsterData);

        //! @section Assert
        $this->assertArrayHasKey('monster', $view);
        $this->assertSame('Squirtle', $view['monster']['name']);
        $this->assertSame(7, $view['monster']['id']);
        $this->assertSame('water', $view['monster']['type1']);
        $this->assertSame('https://img.example/squirtle.png', $view['monster']['image']);
        $this->assertSame(TemplateName::DEX, $view['template']);

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
            type1: MonsterType::GRASS,
            type2: MonsterType::POISON
        );

        $opinionService = $this->createMock(PokemonOpinionService::class);
        $opinionService->method('getOpinion')->willReturn(Result::failure('No opinion'));

        $presenter = new DexPresenter($this->createMock(PokeApiService::class), $opinionService, 300);

        //! @section Act
        $view = $presenter->present($monsterData);

        //! @section Assert
        $this->assertArrayHasKey('monster', $view);
        $this->assertSame('Bulbasaur', $view['monster']['name']);
        $this->assertSame(1, $view['monster']['id']);
        $this->assertSame('grass', $view['monster']['type1']);
        $this->assertSame('poison', $view['monster']['type2']);
        $this->assertSame('https://img.example/bulbasaur.png', $view['monster']['image']);
        $this->assertSame(TemplateName::DEX, $view['template']);

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
            type1: MonsterType::ELECTRIC
        );

        $service = $this->createMock(PokeApiService::class);
        $service->expects($this->once())
            ->method('fetchMonster')
            ->with('25', null, 60) // Verify custom TTL is passed
            ->willReturn(Result::success($monsterData));

        $opinionService = $this->createMock(PokemonOpinionService::class);
        $opinionService->method('getOpinion')->willReturn(Result::failure('No opinion'));

        $presenter = new DexPresenter($service, $opinionService, 60); // Custom 60-second TTL

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

        $opinionService = $this->createMock(PokemonOpinionService::class);
        $opinionService->method('getOpinion')->willReturn(Result::failure('No opinion'));

        $presenter = new DexPresenter($service, $opinionService, 300);

        //! @section Arrange
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Pokemon not found');

        //! @section Act
        $presenter->fetchMonsterData(MonsterIdentifier::fromString('999'));
    }

    public function test_present_includes_opinion_when_available(): void
    {
        //! @section Arrange
        $monsterData = new MonsterData(
            id: 25,
            name: 'Pikachu',
            image: 'https://img.example/pikachu.png',
            type1: MonsterType::ELECTRIC
        );

        $opinionService = $this->createMock(PokemonOpinionService::class);
        $opinionService->method('getOpinion')->willReturn(Result::success([
            'opinion' => 'Test opinion for Pikachu',
            'rating' => 'A'
        ]));

        $presenter = new DexPresenter($this->createMock(PokeApiService::class), $opinionService, 300);

        //! @section Act
        $view = $presenter->present($monsterData);

        //! @section Assert
        $this->assertArrayHasKey('monster', $view);
        $this->assertArrayHasKey('opinion', $view['monster']);
        $this->assertArrayHasKey('rating', $view['monster']);
        $this->assertSame('Test opinion for Pikachu', $view['monster']['opinion']);
        $this->assertSame('A', $view['monster']['rating']);
    }

    public function test_present_excludes_opinion_when_not_available(): void
    {
        //! @section Arrange
        $monsterData = new MonsterData(
            id: 999,
            name: 'Unknown Pokemon',
            image: 'https://img.example/unknown.png',
            type1: MonsterType::NORMAL
        );

        $opinionService = $this->createMock(PokemonOpinionService::class);
        $opinionService->method('getOpinion')->willReturn(Result::failure('No opinion found'));

        $presenter = new DexPresenter($this->createMock(PokeApiService::class), $opinionService, 300);

        //! @section Act
        $view = $presenter->present($monsterData);

        //! @section Assert
        $this->assertArrayHasKey('monster', $view);
        $this->assertArrayNotHasKey('opinion', $view['monster']);
        $this->assertArrayNotHasKey('rating', $view['monster']);
    }
}


