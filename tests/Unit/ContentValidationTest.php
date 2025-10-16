<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

//! @brief Test class for validating Pokemon opinions YAML structure
//! @details This test ensures that the pokemon_opinions.yaml file follows the expected structure
//!          and can be parsed correctly by the application
class ContentValidationTest extends TestCase
{
    private const OPINIONS_FILE = 'content/pokemon_opinions.yaml';

    //! @brief Test that pokemon_opinions.yaml exists and is readable
    public function testPokemonOpinionsFileExists(): void
    {
        //! @section Arrange
        // File path is set as class constant

        //! @section Act & Assert
        $this->assertFileExists(self::OPINIONS_FILE, 'pokemon_opinions.yaml file must exist');
        $this->assertIsReadable(self::OPINIONS_FILE, 'pokemon_opinions.yaml file must be readable');
    }

    //! @brief Test that pokemon_opinions.yaml contains valid YAML syntax
    public function testPokemonOpinionsYamlIsValid(): void
    {
        //! @section Arrange
        $content = file_get_contents(self::OPINIONS_FILE);

        //! @section Act
        $data = Yaml::parse($content);

        //! @section Assert
        $this->assertIsArray($data, 'pokemon_opinions.yaml must parse to an array');
        $this->assertNotEmpty($data, 'pokemon_opinions.yaml must not be empty');
    }

    //! @brief Test that each Pokemon entry has the required structure
    public function testPokemonOpinionsStructure(): void
    {
        //! @section Arrange
        $content = file_get_contents(self::OPINIONS_FILE);
        $data = Yaml::parse($content);
        $validRatings = ['S', 'A', 'B', 'C', 'D'];

        //! @section Act & Assert
        foreach ($data as $pokemonName => $opinionData) {
            $this->assertIsString($pokemonName, "Pokemon name must be a string for entry: $pokemonName");
            $this->assertIsArray($opinionData, "Pokemon '$pokemonName' must have an object structure");

            // Check required fields exist
            $this->assertArrayHasKey('opinion', $opinionData, "Pokemon '$pokemonName' must have 'opinion' field");
            $this->assertArrayHasKey('rating', $opinionData, "Pokemon '$pokemonName' must have 'rating' field");

            // Check field types
            $this->assertIsString($opinionData['opinion'], "Pokemon '$pokemonName' opinion must be a string");
            $this->assertIsString($opinionData['rating'], "Pokemon '$pokemonName' rating must be a string");

            // Check rating is valid
            $this->assertContains(
                $opinionData['rating'],
                $validRatings,
                "Pokemon '$pokemonName' has invalid rating '{$opinionData['rating']}'. Must be one of: " . implode(', ', $validRatings)
            );

            // Check opinion is not empty
            $this->assertNotEmpty(
                trim($opinionData['opinion']),
                "Pokemon '$pokemonName' opinion must not be empty"
            );
        }
    }

    //! @brief Test that all Pokemon names are lowercase (consistent with application logic)
    public function testPokemonNamesAreLowercase(): void
    {
        //! @section Arrange
        $content = file_get_contents(self::OPINIONS_FILE);
        $data = Yaml::parse($content);

        //! @section Act & Assert
        foreach (array_keys($data) as $pokemonName) {
            $this->assertEquals(
                $pokemonName,
                mb_strtolower($pokemonName),
                "Pokemon name '$pokemonName' should be lowercase for consistency"
            );
        }
    }

    //! @brief Test that Pokemon names don't contain invalid characters
    public function testPokemonNamesFormat(): void
    {
        //! @section Arrange
        $content = file_get_contents(self::OPINIONS_FILE);
        $data = Yaml::parse($content);

        //! @section Act & Assert
        foreach (array_keys($data) as $pokemonName) {
            // Pokemon names should be alphanumeric with hyphens (for forms like iron-valiant)
            $this->assertMatchesRegularExpression(
                '/^[a-z0-9-]+$/',
                $pokemonName,
                "Pokemon name '$pokemonName' should contain only lowercase letters, numbers, and hyphens"
            );

            // Should not start or end with hyphen
            $this->assertFalse(str_starts_with($pokemonName, '-'), "Pokemon name '$pokemonName' should not start with hyphen");
            $this->assertFalse(str_ends_with($pokemonName, '-'), "Pokemon name '$pokemonName' should not end with hyphen");
        }
    }

    //! @brief Test that there are no duplicate Pokemon entries
    public function testNoDuplicatePokemonEntries(): void
    {
        //! @section Arrange
        $content = file_get_contents(self::OPINIONS_FILE);
        $data = Yaml::parse($content);

        //! @section Act
        $pokemonNames = array_keys($data);
        $uniqueNames = array_unique($pokemonNames);

        //! @section Assert
        $this->assertEquals(
            count($pokemonNames),
            count($uniqueNames),
            'There should be no duplicate Pokemon entries in the YAML file'
        );
    }
}
