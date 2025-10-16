#!/usr/bin/env php
<?php

declare(strict_types=1);

//! @brief Content validation script for CI/CD pipeline
//! @details This script validates the pokemon_opinions.yaml file for syntax and structure
//!          and can be used in CI pipelines or manually for testing

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

//! @brief Main validation function
//! @return int Exit code (0 for success, 1 for failure)
function validateContent(): int
{
    $opinionsFile = 'content/pokemon_opinions.yaml';

    echo "🔍 Validating Pokemon opinions content...\n\n";

    // Check file exists
    if (!file_exists($opinionsFile)) {
        echo "❌ ERROR: $opinionsFile not found\n";
        return 1;
    }

    echo "✅ File exists: $opinionsFile\n";

    // Validate YAML syntax
    try {
        $content = file_get_contents($opinionsFile);
        $data = Yaml::parse($content);

        if (!is_array($data)) {
            echo "❌ ERROR: $opinionsFile does not contain a valid YAML structure\n";
            return 1;
        }

        echo "✅ YAML syntax is valid\n";
        echo "📊 Found " . count($data) . " Pokemon opinions\n\n";

    } catch (Exception $e) {
        echo "❌ ERROR: Invalid YAML syntax in $opinionsFile: " . $e->getMessage() . "\n";
        return 1;
    }

    // Validate structure
    $validRatings = ['S', 'A', 'B', 'C', 'D'];
    $errors = [];
    $pokemonCount = 0;
    $ratingCounts = [];

    foreach ($data as $pokemonName => $opinionData) {
        $pokemonCount++;

        if (!is_array($opinionData)) {
            $errors[] = "Pokemon '$pokemonName' must have an object structure";
            continue;
        }

        if (!isset($opinionData['opinion'])) {
            $errors[] = "Pokemon '$pokemonName' is missing required 'opinion' field";
        } elseif (!is_string($opinionData['opinion'])) {
            $errors[] = "Pokemon '$pokemonName' opinion must be a string";
        } elseif (trim($opinionData['opinion']) === '') {
            $errors[] = "Pokemon '$pokemonName' opinion must not be empty";
        }

        if (!isset($opinionData['rating'])) {
            $errors[] = "Pokemon '$pokemonName' is missing required 'rating' field";
        } elseif (!in_array($opinionData['rating'], $validRatings)) {
            $errors[] = "Pokemon '$pokemonName' has invalid rating '" . $opinionData['rating'] . "'. Must be one of: " . implode(', ', $validRatings);
        } else {
            $rating = $opinionData['rating'];
            $ratingCounts[$rating] = ($ratingCounts[$rating] ?? 0) + 1;
        }

        // Validate Pokemon name format
        if (!preg_match('/^[a-z0-9-]+$/', $pokemonName)) {
            $errors[] = "Pokemon name '$pokemonName' should contain only lowercase letters, numbers, and hyphens";
        }

        if (str_starts_with($pokemonName, '-') || str_ends_with($pokemonName, '-')) {
            $errors[] = "Pokemon name '$pokemonName' should not start or end with hyphen";
        }
    }

    if (!empty($errors)) {
        echo "❌ Structure validation failed:\n";
        foreach ($errors as $error) {
            echo "   • $error\n";
        }
        echo "\n";
        return 1;
    }

    echo "✅ All $pokemonCount Pokemon opinions have valid structure\n";

    // Show rating distribution
    echo "📈 Rating distribution:\n";
    foreach (['S', 'A', 'B', 'C', 'D'] as $rating) {
        $count = $ratingCounts[$rating] ?? 0;
        $percentage = $pokemonCount > 0 ? round(($count / $pokemonCount) * 100, 1) : 0;
        echo "   $rating: $count ($percentage%)\n";
    }

    echo "\n🎉 All validations passed! Content is ready for deployment.\n";
    return 0;
}

// Run validation if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    exit(validateContent());
}
