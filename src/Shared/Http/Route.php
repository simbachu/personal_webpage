<?php

declare(strict_types=1);

namespace App\Shared\Http;

//! @brief Value object representing a route definition
//!
//! Supports static paths and dynamic `{param}` segments (e.g. `/dex/{id_or_name}`).
//!
//! @code
//! $route = new Route(
//!     '/dex/{id_or_name}',
//!     TemplateName::DEX,
//!     [],
//!     ['handler' => 'dex']
//! );
//! @endcode
class Route
{
    private string $path; //!< Route path pattern
    private TemplateName $template; //!< Template to render
    private array $meta; //!< Metadata for the route (title, description, etc.)
    private array $options; //!< Additional route options (handler, etc.)

    //! @brief Construct a new Route instance
    //! @param path The route path pattern (may include `{param}` segments)
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
        return $this->matchSegments($this->normalizePath($path)) !== null;
    }

    //! @brief Extract parameters from a matching path
    //! @param path The path to extract parameters from
    //! @return array Array of extracted parameters
    public function extractParameters(string $path): array
    {
        return $this->matchSegments($this->normalizePath($path)) ?? [];
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

    //! @brief Match path segments against this route pattern
    //! @param path Normalized request path
    //! @return array<string, string>|null Parameters on match, null otherwise
    private function matchSegments(string $path): ?array
    {
        $patternSegments = $this->path === '/' ? [] : explode('/', trim($this->path, '/'));
        $pathSegments = $path === '/' ? [] : explode('/', trim($path, '/'));

        if (count($patternSegments) !== count($pathSegments)) {
            return null;
        }

        $parameters = [];
        foreach ($patternSegments as $index => $patternSegment) {
            $pathSegment = $pathSegments[$index];

            if (preg_match('/^\{([a-zA-Z_][a-zA-Z0-9_]*)\}$/', $patternSegment, $matches) === 1) {
                if ($pathSegment === '') {
                    return null;
                }
                $parameters[$matches[1]] = $pathSegment;
                continue;
            }

            if ($patternSegment !== $pathSegment) {
                return null;
            }
        }

        return $parameters;
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
