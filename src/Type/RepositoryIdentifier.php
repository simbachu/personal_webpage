<?php

declare(strict_types=1);

namespace App\Type;

use InvalidArgumentException;

//! @brief Identifier for Repository entities
//!
//! Represents a unique identifier for a Repository entity, typically in the
//! format "owner/repository" for GitHub repositories. This provides type
//! safety when working with Repository identifiers throughout the system.
//!
//! @code
//! // Example usage:
//! $repoId = RepositoryIdentifier::fromString('simbachu/personal_webpage');
//!
//! echo $repoId->getOwner(); // "simbachu"
//! echo $repoId->getRepository(); // "personal_webpage"
//! echo $repoId->getValue(); // "simbachu/personal_webpage"
//!
//! // Use in service calls
//! $service->getRepositoryInfo($repoId);
//! @endcode
final class RepositoryIdentifier extends Identifier
{
    private readonly string $owner;
    private readonly string $repository;

    //! @brief Validate that the Repository identifier is valid
    //! @param value The identifier value to validate (should be "owner/repository")
    //! @throws \InvalidArgumentException If the identifier format is invalid
    protected function validate(string $value): void
    {
        $parts = explode('/', $value, 2);
        if (count($parts) !== 2) {
            throw new InvalidArgumentException(
                'Repository identifier must contain exactly one slash. Got: ' . $value
            );
        }

        [$owner, $repository] = $parts;

        // Validate owner name
        if (!preg_match('/^[a-zA-Z0-9\-_\.]+$/', $owner) || strlen($owner) > 100) {
            throw new InvalidArgumentException(
                'Repository owner must contain only alphanumeric characters, hyphens, underscores, and dots. Got: ' . $owner
            );
        }

        // Validate repository name
        if (!preg_match('/^[a-zA-Z0-9\-_\.]+$/', $repository) || strlen($repository) > 100) {
            throw new InvalidArgumentException(
                'Repository name must contain only alphanumeric characters, hyphens, underscores, and dots. Got: ' . $repository
            );
        }

        // Store the parsed components
        $this->owner = $owner;
        $this->repository = $repository;
    }

    //! @brief Get the repository owner
    //! @return string The owner name
    public function getOwner(): string
    {
        return $this->owner;
    }

    //! @brief Get the repository name
    //! @return string The repository name
    public function getRepository(): string
    {
        return $this->repository;
    }

    //! @brief Create from owner and repository name
    //! @param owner The repository owner
    //! @param repository The repository name
    //! @return self New RepositoryIdentifier
    public static function fromOwnerAndRepository(string $owner, string $repository): self
    {
        return new self($owner . '/' . $repository);
    }

    //! @brief Create from a full repository path string
    //! @param fullPath The full repository path (e.g., "owner/repository")
    //! @return static New RepositoryIdentifier
    public static function fromString(string $fullPath): static
    {
        return new self($fullPath);
    }
}
