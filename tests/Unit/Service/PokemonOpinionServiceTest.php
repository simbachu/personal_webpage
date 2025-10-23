<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Service\PokemonOpinionService;
use App\Service\PokeApiService;
use App\Type\Result;
use App\Type\MonsterIdentifier;

final class PokemonOpinionServiceTest extends TestCase
{
    private const TEST_OPINIONS_FILE = 'content/pokemon_opinions_test.yaml';

    //! @brief Create test YAML file with opinion data
    //! @param content YAML content to write
    private function createTestOpinionsFile(string $content): void
    {
        file_put_contents(self::TEST_OPINIONS_FILE, $content);
    }

    //! @brief Clean up test file
    private function cleanupTestFile(): void
    {
        if (file_exists(self::TEST_OPINIONS_FILE)) {
            unlink(self::TEST_OPINIONS_FILE);
        }
    }

    protected function setUp(): void
    {
        $this->cleanupTestFile();
    }

    protected function tearDown(): void
    {
        $this->cleanupTestFile();
    }

    public function testGetOpinionByNameReturnsSuccessWhenOpinionExists(): void
    {
        //! @section Arrange
        $this->createTestOpinionsFile(<<<YAML
pikachu:
  opinion: "My sister caught a wild Pikachu in Viridian Forest in her version and I didn't get one until I got Yellow. Crazy. Of course its the most iconic Pokémon ever, but it's well deserved."
  rating: A
YAML);

        $service = new PokemonOpinionService(self::TEST_OPINIONS_FILE);

        //! @section Act
        $result = $service->getOpinion(MonsterIdentifier::fromString('pikachu'));

        //! @section Assert
        $this->assertTrue($result->isSuccess());
        $opinion = $result->getValue();
        $this->assertArrayHasKey('opinion', $opinion);
        $this->assertArrayHasKey('rating', $opinion);
        $this->assertSame('A', $opinion['rating']);
        $this->assertStringContainsString('My sister caught a wild Pikachu', $opinion['opinion']);
    }

    public function testGetOpinionByNameHandlesCaseInsensitiveLookup(): void
    {
        //! @section Arrange
        $this->createTestOpinionsFile(<<<YAML
pikachu:
  opinion: "Test opinion"
  rating: B
YAML);

        $service = new PokemonOpinionService(self::TEST_OPINIONS_FILE);

        //! @section Act
        $result = $service->getOpinion(MonsterIdentifier::fromString('PIKACHU'));

        //! @section Assert
        $this->assertTrue($result->isSuccess());
        $opinion = $result->getValue();
        $this->assertSame('Test opinion', $opinion['opinion']);
    }

    public function testGetOpinionByNameTrimsWhitespace(): void
    {
        //! @section Arrange
        $this->createTestOpinionsFile(<<<YAML
pikachu:
  opinion: "Test opinion"
  rating: B
YAML);

        $service = new PokemonOpinionService(self::TEST_OPINIONS_FILE);

        //! @section Act
        $result = $service->getOpinion(MonsterIdentifier::fromString('  pikachu  '));

        //! @section Assert
        $this->assertTrue($result->isSuccess());
        $opinion = $result->getValue();
        $this->assertSame('Test opinion', $opinion['opinion']);
    }

    public function testGetOpinionByNameReturnsFailureWhenOpinionDoesNotExist(): void
    {
        //! @section Arrange
        $this->createTestOpinionsFile(<<<YAML
pikachu:
  opinion: "Test opinion"
  rating: B
YAML);

        $service = new PokemonOpinionService(self::TEST_OPINIONS_FILE);

        //! @section Act
        $result = $service->getOpinion(MonsterIdentifier::fromString('charizard'));

        //! @section Assert
        $this->assertTrue($result->isFailure());
        $this->assertStringContainsString('No opinion found for Pokemon: charizard', $result->getError());
    }

    public function testGetOpinionByNumericIdFetchesNameFromPokeApi(): void
    {
        //! @section Arrange
        $this->createTestOpinionsFile(<<<YAML
pikachu:
  opinion: "Test opinion for Pikachu"
  rating: A
YAML);

        // Create service with mocked PokeAPI
        $service = new class(self::TEST_OPINIONS_FILE) extends PokemonOpinionService {
            public function normalizeToName(MonsterIdentifier $identifier): ?string {
                $value = $identifier->getValue();
                if (!is_numeric($value)) {
                    return $value;
                }
                // For test purposes, just return 'pikachu' for ID 25
                return $value === '25' ? 'pikachu' : null;
            }
        };

        //! @section Act
        $result = $service->getOpinion(MonsterIdentifier::fromString('25'));

        //! @section Assert
        $this->assertTrue($result->isSuccess());
        $opinion = $result->getValue();
        $this->assertSame('Test opinion for Pikachu', $opinion['opinion']);
    }

    public function testGetOpinionByNumericIdReturnsFailureWhenPokeApiFails(): void
    {
        //! @section Arrange
        $this->createTestOpinionsFile(<<<YAML
pikachu:
  opinion: "Test opinion"
  rating: B
YAML);

        // Create service with mocked PokeAPI that returns null for normalization
        $service = new class(self::TEST_OPINIONS_FILE) extends PokemonOpinionService {
            public function normalizeToName(MonsterIdentifier $identifier): ?string {
                $value = $identifier->getValue();
                if (!is_numeric($value)) {
                    return $value;
                }
                // Return null to simulate API failure
                return null;
            }
        };

        //! @section Act
        $result = $service->getOpinion(MonsterIdentifier::fromString('25'));

        //! @section Assert
        $this->assertTrue($result->isFailure());
        $this->assertStringContainsString('Unable to normalize identifier to Pokemon name', $result->getError());
    }

    public function testHasOpinionReturnsTrueWhenOpinionExists(): void
    {
        //! @section Arrange
        $this->createTestOpinionsFile(<<<YAML
pikachu:
  opinion: "Test opinion"
  rating: B
YAML);

        $service = new PokemonOpinionService(self::TEST_OPINIONS_FILE);

        //! @section Act
        $hasOpinion = $service->hasOpinion(MonsterIdentifier::fromString('pikachu'));

        //! @section Assert
        $this->assertTrue($hasOpinion);
    }

    public function testHasOpinionReturnsFalseWhenOpinionDoesNotExist(): void
    {
        //! @section Arrange
        $this->createTestOpinionsFile(<<<YAML
pikachu:
  opinion: "Test opinion"
  rating: B
YAML);

        $service = new PokemonOpinionService(self::TEST_OPINIONS_FILE);

        //! @section Act
        $hasOpinion = $service->hasOpinion(MonsterIdentifier::fromString('charizard'));

        //! @section Assert
        $this->assertFalse($hasOpinion);
    }

    public function testGetAllOpinionNamesReturnsArrayOfNames(): void
    {
        //! @section Arrange
        $this->createTestOpinionsFile(<<<YAML
pikachu:
  opinion: "Test opinion 1"
  rating: A
charizard:
  opinion: "Test opinion 2"
  rating: S
blastoise:
  opinion: "Test opinion 3"
  rating: B
YAML);

        $service = new PokemonOpinionService(self::TEST_OPINIONS_FILE);

        //! @section Act
        $names = $service->getAllOpinionNames();

        //! @section Assert
        $this->assertIsArray($names);
        $this->assertCount(3, $names);
        $this->assertContains('pikachu', $names);
        $this->assertContains('charizard', $names);
        $this->assertContains('blastoise', $names);
    }

    public function testGetAllOpinionNamesReturnsEmptyArrayWhenFileDoesNotExist(): void
    {
        //! @section Arrange
        $service = new PokemonOpinionService(self::TEST_OPINIONS_FILE);

        //! @section Act
        $names = $service->getAllOpinionNames();

        //! @section Assert
        $this->assertIsArray($names);
        $this->assertEmpty($names);
    }

    public function testGetOpinionReturnsFailureWhenFileDoesNotExist(): void
    {
        //! @section Arrange
        $service = new PokemonOpinionService(self::TEST_OPINIONS_FILE);

        //! @section Act
        $result = $service->getOpinion(MonsterIdentifier::fromString('pikachu'));

        //! @section Assert
        $this->assertTrue($result->isFailure());
        $this->assertStringContainsString('Failed to load opinions data', $result->getError());
    }

    public function testGetOpinionReturnsFailureWhenYamlIsInvalid(): void
    {
        //! @section Arrange
        $this->createTestOpinionsFile('invalid yaml content: [unclosed bracket');

        $service = new PokemonOpinionService(self::TEST_OPINIONS_FILE);

        //! @section Act
        $result = $service->getOpinion(MonsterIdentifier::fromString('pikachu'));

        //! @section Assert
        $this->assertTrue($result->isFailure());
        $this->assertStringContainsString('Failed to load opinions data', $result->getError());
    }

    public function testGetOpinionCachesDataAfterFirstLoad(): void
    {
        //! @section Arrange
        $this->createTestOpinionsFile(<<<YAML
pikachu:
  opinion: "Test opinion"
  rating: B
YAML);

        $service = new PokemonOpinionService(self::TEST_OPINIONS_FILE);

        //! @section Act
        $result1 = $service->getOpinion(MonsterIdentifier::fromString('pikachu'));

        // Delete the file to ensure caching is working
        $this->cleanupTestFile();

        $result2 = $service->getOpinion(MonsterIdentifier::fromString('pikachu'));

        //! @section Assert
        $this->assertTrue($result1->isSuccess());
        $this->assertTrue($result2->isSuccess());
        $this->assertEquals($result1->getValue(), $result2->getValue());
    }

    public function testGetOpinionHandlesComplexOpinionData(): void
    {
        //! @section Arrange
        $this->createTestOpinionsFile(<<<YAML
iron-valiant:
  opinion: "Now this is a design!! So cool with the cape dress and helmet."
  rating: S
YAML);

        $service = new PokemonOpinionService(self::TEST_OPINIONS_FILE);

        //! @section Act
        $result = $service->getOpinion(MonsterIdentifier::fromString('iron-valiant'));

        //! @section Assert
        $this->assertTrue($result->isSuccess());
        $opinion = $result->getValue();
        $this->assertSame('S', $opinion['rating']);
        $this->assertStringContainsString('cape dress and helmet', $opinion['opinion']);
    }

    public function test_get_opinion_uses_species_name_for_maushold_variants(): void
    {
        //! @section Arrange
        $this->createTestOpinionsFile(<<<YAML
maushold:
  opinion: "They are such a cute little family! So delightful!"
  rating: A
YAML);

        $service = new PokemonOpinionService(self::TEST_OPINIONS_FILE);

        //! @section Act
        $result1 = $service->getOpinion(MonsterIdentifier::fromString('maushold-family-of-four'));
        $result2 = $service->getOpinion(MonsterIdentifier::fromString('maushold-family-of-three'));

        //! @section Assert
        $this->assertTrue($result1->isSuccess());
        $this->assertTrue($result2->isSuccess());

        $opinion1 = $result1->getValue();
        $opinion2 = $result2->getValue();

        // Both should return the same rating for maushold
        $this->assertSame('A', $opinion1['rating']);
        $this->assertSame('A', $opinion2['rating']);
        $this->assertSame($opinion1['opinion'], $opinion2['opinion']);
    }

    public function test_get_opinion_uses_species_name_for_deoxys_forms(): void
    {
        //! @section Arrange
        $this->createTestOpinionsFile(<<<YAML
deoxys:
  opinion: "The different forms are quite cool, but I think the concept is a bit overdone. Still, the design is solid."
  rating: B
YAML);

        $service = new PokemonOpinionService(self::TEST_OPINIONS_FILE);

        //! @section Act
        $result1 = $service->getOpinion(MonsterIdentifier::fromString('deoxys-normal'));
        $result2 = $service->getOpinion(MonsterIdentifier::fromString('deoxys-attack'));

        //! @section Assert
        $this->assertTrue($result1->isSuccess());
        $this->assertTrue($result2->isSuccess());

        $opinion1 = $result1->getValue();
        $opinion2 = $result2->getValue();

        // Both should return the same rating for deoxys
        $this->assertSame('B', $opinion1['rating']);
        $this->assertSame('B', $opinion2['rating']);
        $this->assertSame($opinion1['opinion'], $opinion2['opinion']);
    }
}
