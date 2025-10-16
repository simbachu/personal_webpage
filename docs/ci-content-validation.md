# Content Branch Validation

This document describes the CI workflow for validating content branches, specifically for the Pokemon opinions YAML file.

## Overview

The `validate-content.yml` workflow automatically validates content changes when:
- Pushing to branches matching `content/**` pattern
- Creating pull requests targeting `main` or `content/**` branches

## Validation Steps

### 1. YAML Syntax Validation
- Ensures `content/pokemon_opinions.yaml` exists and is readable
- Validates YAML syntax using Symfony YAML parser
- Confirms the file contains a valid array/object structure

### 2. Structure Validation
- Validates each Pokemon entry has required fields:
  - `opinion`: String field with the Pokemon opinion text
  - `rating`: String field with valid rating (S, A, B, C, D)
- Ensures Pokemon names follow naming conventions:
  - Lowercase letters, numbers, and hyphens only
  - No leading or trailing hyphens
  - No duplicate entries

### 3. Compatibility Testing
- Runs existing unit tests to ensure changes don't break application functionality
- Specifically runs `PokemonOpinionServiceTest` to verify YAML parsing works correctly

### 4. Automatic PR Creation
When validation succeeds on a content branch:
- Creates a pull request from the content branch to `main`
- Uses descriptive title and body with validation results
- Adds appropriate labels (`content-update`, `auto-generated`)
- Deletes the merge branch after PR creation

## Manual Validation

You can run content validation manually using:

```bash
php bin/validate-content.php
```

This script provides detailed output including:
- File existence check
- YAML syntax validation
- Structure validation with error details
- Rating distribution statistics

## Expected YAML Structure

```yaml
pokemon-name:
  opinion: "Your opinion text here"
  rating: "A"  # Must be S, A, B, C, or D
```

## Validation Rules

1. **File Requirements**:
   - File must exist at `content/pokemon_opinions.yaml`
   - Must be valid YAML syntax
   - Must parse to an associative array

2. **Pokemon Entry Requirements**:
   - Each entry must have `opinion` and `rating` fields
   - `opinion` must be a non-empty string
   - `rating` must be exactly one of: S, A, B, C, D
   - Pokemon name must be lowercase alphanumeric with hyphens
   - No duplicate Pokemon names

3. **Naming Conventions**:
   - Pokemon names should be lowercase
   - Use hyphens for special forms (e.g., `iron-valiant`)
   - No leading or trailing hyphens

## Error Handling

The workflow will fail if:
- YAML syntax is invalid
- Required fields are missing
- Invalid ratings are used
- Pokemon names don't follow conventions
- Existing tests fail

All errors include detailed messages to help identify and fix issues.

## Integration with Existing Tests

The validation integrates with the existing test suite:
- Uses the same YAML parsing logic as the application
- Runs `PokemonOpinionServiceTest` to ensure compatibility
- Follows the same validation patterns as unit tests

This ensures that content validation matches the actual application behavior.
