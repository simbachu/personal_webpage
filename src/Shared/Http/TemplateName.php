<?php

declare(strict_types=1);

namespace App\Shared\Http;

//! @brief Enum representing valid template names used throughout the application
//!
//! This enum provides type safety for template names, preventing typos and ensuring
//! only valid templates can be used. All template names must correspond to actual
//! .twig files in the templates directory.
//!
//! @code
//! // Example usage:
//! $template = TemplateName::HOME;
//! echo $template->value; // "home"
//!
//! // Use in routing
//! $route = ['template' => TemplateName::DEX];
//!
//! // Use in presenters
//! return ['template' => TemplateName::NOT_FOUND];
//! @endcode
enum TemplateName: string
{
    case HOME = 'home';
    case DEX = 'dex';
    case ARTICLE = 'article';
    case NOT_FOUND = '404';

    //! @brief Get all valid template names as an array
    //! @return string[] Array of all template name values
    public static function getAllValues(): array
    {
        return array_column(self::cases(), 'value');
    }

    //! @brief Check if a string represents a valid template name
    //! @param templateName The template name string to validate
    //! @return bool True if the template name is valid
    public static function isValid(string $templateName): bool
    {
        return in_array($templateName, self::getAllValues(), true);
    }

    //! @brief Create TemplateName from string with validation
    //! @param templateName The template name string
    //! @return self The corresponding TemplateName enum case
    //! @throws \InvalidArgumentException If the template name is not valid
    public static function fromString(string $templateName): self
    {
        return match ($templateName) {
            'home' => self::HOME,
            'dex' => self::DEX,
            'article' => self::ARTICLE,
            '404' => self::NOT_FOUND,
            default => throw new \InvalidArgumentException(
                "Invalid template name: '{$templateName}'. Valid templates are: " . implode(', ', self::getAllValues())
            ),
        };
    }

    //! @brief Get a human-readable description of this template
    //! @return string Description of what this template represents
    public function getDescription(): string
    {
        return match ($this) {
            self::HOME => 'Home page template',
            self::DEX => 'Pokemon dex detail page template',
            self::ARTICLE => 'Article/blog post template',
            self::NOT_FOUND => '404 error page template',
        };
    }

    //! @brief Check if this template represents an error page
    //! @return bool True if this is an error template
    public function isErrorTemplate(): bool
    {
        return $this === self::NOT_FOUND;
    }

    //! @brief Check if this template represents a content page
    //! @return bool True if this is a content template (not error)
    public function isContentTemplate(): bool
    {
        return !$this->isErrorTemplate();
    }

    //! @brief Convert to string representation
    //! @return string The template name value
    public function toString(): string
    {
        return $this->value;
    }

    //! @brief Backward-compatible accessor matching other value objects
    //! @return string The raw template name value
    public function getValue(): string
    {
        return $this->value;
    }

    //! @brief Twig loader namespace for this template's slice
    //! @return string One of: site, dex, shared
    public function getTwigNamespace(): string
    {
        return match ($this) {
            self::HOME, self::ARTICLE => 'site',
            self::DEX => 'dex',
            self::NOT_FOUND => 'shared',
        };
    }

    //! @brief Get the Twig template path including namespace (e.g. "@site/home.twig")
    //! @return string Namespaced Twig path
    public function toTwigPath(): string
    {
        return '@' . $this->getTwigNamespace() . '/' . $this->value . '.twig';
    }

    //! @brief Filename within the slice templates directory
    //! @return string The filename with .twig extension (e.g., "home.twig")
    public function toFileName(): string
    {
        return $this->value . '.twig';
    }

    //! @brief Build a FilePath to this template under a slice templates directory
    //! @param templatesDir Directory for this template's Twig namespace
    //! @return FilePath Full path to the twig file
    public function toPath(\App\Shared\Support\FilePath $templatesDir): \App\Shared\Support\FilePath
    {
        return $templatesDir->join($this->toFileName());
    }

    //! @brief Ensure this template file exists under the matching namespace path
    //! @param namespacePaths Map of Twig namespace => templates directory
    //! @return FilePath Full path to the twig file
    //! @throws \RuntimeException If the template file does not exist
    //! @throws \InvalidArgumentException If the namespace path is not configured
    public function ensureExists(array $namespacePaths): \App\Shared\Support\FilePath
    {
        $namespace = $this->getTwigNamespace();
        if (!isset($namespacePaths[$namespace])) {
            throw new \InvalidArgumentException("No templates path configured for namespace '{$namespace}'");
        }

        $templatesDir = $namespacePaths[$namespace];
        if (!$templatesDir instanceof \App\Shared\Support\FilePath) {
            $templatesDir = \App\Shared\Support\FilePath::fromString((string) $templatesDir);
        }

        $path = $this->toPath($templatesDir);
        if (!$path->exists() || !$path->isFile()) {
            throw new \RuntimeException('Template not found: ' . $path->getValue());
        }
        return $path;
    }
}
