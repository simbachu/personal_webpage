<?php

declare(strict_types=1);

namespace App\Type;

use InvalidArgumentException;

//! @brief Value object representing a Git branch name
//!
//! Encapsulates a Git branch name with validation to ensure it follows
//! Git branch naming conventions. This provides type safety when working
//! with branch names throughout the system.
//!
//! @code
//! // Example usage:
//! $branch = BranchName::fromString('main');
//! $devBranch = BranchName::fromString('developing');
//!
//! echo $branch->getValue(); // "main"
//! echo $devBranch->getValue(); // "developing"
//!
//! // Use in service calls
//! $service->getRepositoryInfo($repoId, $branch);
//! @endcode
final class BranchName
{
    private readonly string $name;

    //! @brief Construct a new BranchName instance
    //! @param name The branch name
    //! @throws \InvalidArgumentException If the branch name is invalid
    public function __construct(string $name)
    {
        $trimmed = trim($name);
        if ($trimmed === '') {
            throw new InvalidArgumentException('Branch name cannot be empty');
        }

        $this->validate($trimmed);
        $this->name = $trimmed;
    }

    //! @brief Validate that the branch name follows Git conventions
    //! @param name The branch name to validate
    //! @throws \InvalidArgumentException If the branch name is invalid
    private function validate(string $name): void
    {
        // Git branch names cannot start with a dot or contain certain characters
        if (str_starts_with($name, '.')) {
            throw new InvalidArgumentException(
                'Branch name cannot start with a dot. Got: ' . $name
            );
        }

        // Cannot contain spaces, tilde, caret, colon, question mark, asterisk, or brackets
        if (preg_match('/[\s~^:?*\[\]]/', $name)) {
            throw new InvalidArgumentException(
                'Branch name cannot contain spaces, tilde, caret, colon, question mark, asterisk, or brackets. Got: ' . $name
            );
        }

        // Cannot end with a dot or slash
        if (str_ends_with($name, '.') || str_ends_with($name, '/')) {
            throw new InvalidArgumentException(
                'Branch name cannot end with a dot or slash. Got: ' . $name
            );
        }

        // Cannot be too long (Git has a limit of around 255 characters, but we'll be more conservative)
        if (strlen($name) > 100) {
            throw new InvalidArgumentException(
                'Branch name cannot exceed 100 characters. Got: ' . $name
            );
        }

        // Cannot be a reserved name
        $reserved = ['HEAD', 'head', 'HEAD/', 'head/'];
        if (in_array($name, $reserved, true)) {
            throw new InvalidArgumentException(
                'Branch name cannot be a reserved Git name. Got: ' . $name
            );
        }
    }

    //! @brief Get the branch name
    //! @return string The branch name
    public function getValue(): string
    {
        return $this->name;
    }

    //! @brief Check if this branch name equals another
    //! @param other The other branch name to compare with
    //! @return bool True if both branch names are equal
    public function equals(BranchName $other): bool
    {
        return $this->name === $other->name;
    }

    //! @brief Convert to string representation
    //! @return string The branch name as a string
    public function __toString(): string
    {
        return $this->name;
    }

    //! @brief Create from string
    //! @param name The branch name string
    //! @return self New BranchName instance
    public static function fromString(string $name): self
    {
        return new self($name);
    }
}
