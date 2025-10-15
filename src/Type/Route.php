<?php

declare(strict_types=1);

namespace App\Type;

//! @brief Value object representing a route definition
//!
//! This class encapsulates route information including the path pattern,
//! template, metadata, and handler configuration. It provides type safety
//! and validation for route definitions.
//!
//! @code
//! // Example usage:
//! $route = new Route(
//!     '/',
//!     TemplateName::HOME,
//!     ['title' => 'Home Page'],
//!     ['handler' => 'home']
//! );
//!
//! echo $route->getPath(); // "/"
//! echo $route->getTemplate()->value; // "home"
//! @endcode
class Route
{
    private string $path; //!< Route path pattern
    private TemplateName $template; //!< Template to render
    private array $meta; //!< Metadata for the route (title, description, etc.)
    private array $options; //!< Additional route options (handler, etc.)

    //! @brief Construct a new Route instance
    //! @param path The route path pattern
    //! @param template The template to render for this route
    //! @param meta Optional metadata array (title, description, etc.)
    //! @param options Optional additional options (handler, etc.)
    public function __construct(
        string $path,
        TemplateName $template,
        array $meta = [],
        array $options = []
    ) {
        $this->path = $this->normalizePath($path);
        $this->template = $template;
        $this->meta = $meta;
        $this->options = $options;
    }

    //! @brief Get the route path pattern
    //! @return string The normalized route path
    public function getPath(): string
    {
        return $this->path;
    }

    //! @brief Get the template for this route
    //! @return TemplateName The template enum value
    public function getTemplate(): TemplateName
    {
        return $this->template;
    }

    //! @brief Get the metadata for this route
    //! @return array The metadata array
    public function getMeta(): array
    {
        return $this->meta;
    }

    //! @brief Get the options for this route
    //! @return array The options array
    public function getOptions(): array
    {
        return $this->options;
    }

    //! @brief Get a specific metadata value
    //! @param key The metadata key
    //! @param default Default value if key doesn't exist
    //! @return mixed The metadata value or default
    public function getMetaValue(string $key, mixed $default = null): mixed
    {
        return $this->meta[$key] ?? $default;
    }

    //! @brief Get a specific option value
    //! @param key The option key
    //! @param default Default value if key doesn't exist
    //! @return mixed The option value or default
    public function getOption(string $key, mixed $default = null): mixed
    {
        return $this->options[$key] ?? $default;
    }

    //! @brief Check if this route matches a given path
    //! @param path The path to check against
    //! @return bool True if the path matches this route
    public function matches(string $path): bool
    {
        $normalizedPath = $this->normalizePath($path);

        // Exact match for static routes
        if ($this->path === $normalizedPath) {
            return true;
        }

        // Check for dynamic route patterns (e.g., /dex/{id})
        return $this->matchesDynamicPattern($normalizedPath);
    }

    //! @brief Extract parameters from a matching path
    //! @param path The path to extract parameters from
    //! @return array Array of extracted parameters
    public function extractParameters(string $path): array
    {
        $normalizedPath = $this->normalizePath($path);

        // For exact matches, no parameters
        if ($this->path === $normalizedPath) {
            return [];
        }

        // Extract parameters from dynamic patterns
        return $this->extractDynamicParameters($normalizedPath);
    }

    //! @brief Normalize a path by removing trailing slashes (except root)
    //! @param path The path to normalize
    //! @return string The normalized path
    private function normalizePath(string $path): string
    {
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }
        return $path;
    }

    //! @brief Check if path matches dynamic pattern (e.g., /dex/{id})
    //! @param path The path to check
    //! @return bool True if matches dynamic pattern
    private function matchesDynamicPattern(string $path): bool
    {
        // Simple dynamic route matching for /dex/{id_or_name}
        if ($this->path === '/dex' && str_starts_with($path, '/dex/')) {
            $segments = explode('/', trim($path, '/'));
            return count($segments) === 2 && $segments[0] === 'dex';
        }

        return false;
    }

    //! @brief Extract parameters from dynamic path patterns
    //! @param path The path to extract from
    //! @return array Extracted parameters
    private function extractDynamicParameters(string $path): array
    {
        if ($this->path === '/dex' && str_starts_with($path, '/dex/')) {
            $segments = explode('/', trim($path, '/'));
            if (count($segments) === 2 && $segments[0] === 'dex') {
                return ['id_or_name' => $segments[1]];
            }
        }

        return [];
    }

    //! @brief Create a route with merged metadata
    //! @param meta Additional metadata to merge
    //! @return Route New route with merged metadata
    public function withMeta(array $meta): self
    {
        return new self(
            $this->path,
            $this->template,
            array_merge($this->meta, $meta),
            $this->options
        );
    }

    //! @brief Create a route with merged options
    //! @param options Additional options to merge
    //! @return Route New route with merged options
    public function withOptions(array $options): self
    {
        return new self(
            $this->path,
            $this->template,
            $this->meta,
            array_merge($this->options, $options)
        );
    }
}
