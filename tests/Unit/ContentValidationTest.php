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

        //! @section Act
        $fileExists = file_exists(self::OPINIONS_FILE);
        $fileIsReadable = is_readable(self::OPINIONS_FILE);

        //! @section Assert
        $this->assertTrue($fileExists, 'pokemon_opinions.yaml file must exist');
        $this->assertTrue($fileIsReadable, 'pokemon_opinions.yaml file must be readable');
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

        //! @section Act
        $validationResults = [];
        foreach ($data as $pokemonName => $opinionData) {
            $validationResults[$pokemonName] = [
                'isValidName' => is_string($pokemonName),
                'isValidStructure' => is_array($opinionData),
                'hasOpinion' => isset($opinionData['opinion']),
                'hasRating' => isset($opinionData['rating']),
                'opinionIsString' => is_string($opinionData['opinion'] ?? null),
                'ratingIsString' => is_string($opinionData['rating'] ?? null),
                'ratingIsValid' => in_array($opinionData['rating'] ?? null, $validRatings),
                'opinionNotEmpty' => !empty(trim($opinionData['opinion'] ?? ''))
            ];
        }

        //! @section Assert
        foreach ($data as $pokemonName => $opinionData) {
            $result = $validationResults[$pokemonName];

            $this->assertTrue($result['isValidName'], "Pokemon name must be a string for entry: $pokemonName");
            $this->assertTrue($result['isValidStructure'], "Pokemon '$pokemonName' must have an object structure");

            // Check required fields exist
            $this->assertTrue($result['hasOpinion'], "Pokemon '$pokemonName' must have 'opinion' field");
            $this->assertTrue($result['hasRating'], "Pokemon '$pokemonName' must have 'rating' field");

            // Check field types
            $this->assertTrue($result['opinionIsString'], "Pokemon '$pokemonName' opinion must be a string");
            $this->assertTrue($result['ratingIsString'], "Pokemon '$pokemonName' rating must be a string");

            // Check rating is valid
            $this->assertTrue(
                $result['ratingIsValid'],
                "Pokemon '$pokemonName' has invalid rating '{$opinionData['rating']}'. Must be one of: " . implode(', ', $validRatings)
            );

            // Check opinion is not empty
            $this->assertTrue(
                $result['opinionNotEmpty'],
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

        //! @section Act
        $nameValidationResults = [];
        foreach (array_keys($data) as $pokemonName) {
            $nameValidationResults[$pokemonName] = [
                'isLowercase' => $pokemonName === mb_strtolower($pokemonName)
            ];
        }

        //! @section Assert
        foreach (array_keys($data) as $pokemonName) {
            $result = $nameValidationResults[$pokemonName];
            $this->assertTrue(
                $result['isLowercase'],
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

        //! @section Act
        $formatValidationResults = [];
        foreach (array_keys($data) as $pokemonName) {
            $formatValidationResults[$pokemonName] = [
                'matchesPattern' => preg_match('/^[a-z0-9-]+$/', $pokemonName) === 1,
                'doesNotStartWithHyphen' => !str_starts_with($pokemonName, '-'),
                'doesNotEndWithHyphen' => !str_ends_with($pokemonName, '-')
            ];
        }

        //! @section Assert
        foreach (array_keys($data) as $pokemonName) {
            $result = $formatValidationResults[$pokemonName];

            // Pokemon names should be alphanumeric with hyphens (for forms like iron-valiant)
            $this->assertTrue(
                $result['matchesPattern'],
                "Pokemon name '$pokemonName' should contain only lowercase letters, numbers, and hyphens"
            );

            // Should not start or end with hyphen
            $this->assertTrue($result['doesNotStartWithHyphen'], "Pokemon name '$pokemonName' should not start with hyphen");
            $this->assertTrue($result['doesNotEndWithHyphen'], "Pokemon name '$pokemonName' should not end with hyphen");
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
