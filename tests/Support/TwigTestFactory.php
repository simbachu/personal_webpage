<?php

declare(strict_types=1);

namespace Tests\Support;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

//! @brief Shared Twig setup for tests using slice template namespaces
final class TwigTestFactory
{
    //! @brief Absolute path to the project root
    public static function projectRoot(): string
    {
        return dirname(__DIR__, 2);
    }

    //! @brief Minimal layout variables required by @shared/layout.twig
    public static function layoutGlobals(): array
    {
        return [
            'base_url' => 'https://example.test',
            'current_url' => 'https://example.test/dex',
            'canonical_url' => 'https://example.test/dex',
            'cache_bust' => '1',
            'current_year' => '2026',
            'github' => [
                'main' => null,
                'dev' => null,
                'commits_ahead' => 0,
            ],
            'meta' => [
                'title' => 'Test',
            ],
        ];
    }

    //! @brief Filesystem loader with @shared, @site, and @dex namespaces
    public static function createLoader(): FilesystemLoader
    {
        $root = self::projectRoot();
        $loader = new FilesystemLoader();
        $loader->addPath($root . '/src/Shared/templates', 'shared');
        $loader->addPath($root . '/src/Site/templates', 'site');
        $loader->addPath($root . '/src/Dex/templates', 'dex');
        return $loader;
    }

    //! @brief Twig environment with slice template namespaces
    public static function createEnvironment(array $options = []): Environment
    {
        $defaults = [
            'cache' => false,
            'debug' => false,
            'strict_variables' => false,
        ];

        return new Environment(self::createLoader(), array_merge($defaults, $options));
    }
}
