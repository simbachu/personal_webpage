<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Http;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use App\Shared\Http\TemplateName;
use App\Shared\Support\FilePath;

//! @brief Compact TemplateName contracts (invalid input, paths, ensureExists)
class TemplateNameTest extends TestCase
{
    #[DataProvider('provideTemplates')]
    public function test_from_string_round_trips_and_builds_twig_path(
        string $value,
        TemplateName $expected,
        string $namespace,
        string $twigPath,
        string $description
    ): void {
        //! @section Act
        $template = TemplateName::fromString($value);

        //! @section Assert
        $this->assertSame($expected, $template);
        $this->assertTrue(TemplateName::isValid($value));
        $this->assertSame($namespace, $template->getTwigNamespace());
        $this->assertSame($twigPath, $template->toTwigPath());
        $this->assertSame($value . '.twig', $template->toFileName());
        $this->assertSame($description, $template->getDescription());
    }

    //! @return array<string, array{0: string, 1: TemplateName, 2: string, 3: string, 4: string}>
    public static function provideTemplates(): array
    {
        return [
            'home' => ['home', TemplateName::HOME, 'site', '@site/home.twig', 'Home page template'],
            'dex' => ['dex', TemplateName::DEX, 'dex', '@dex/dex.twig', 'Pokemon dex detail page template'],
            'article' => ['article', TemplateName::ARTICLE, 'site', '@site/article.twig', 'Article/blog post template'],
            'cv' => ['cv', TemplateName::CV, 'cv', '@cv/cv.twig', 'CV page template'],
            'benefactor' => ['benefactor', TemplateName::BENEFACTOR, 'benefactor', '@benefactor/benefactor.twig', 'Benefactor Patreon member list template'],
            '404' => ['404', TemplateName::NOT_FOUND, 'shared', '@shared/404.twig', '404 error page template'],
        ];
    }

    public function test_from_string_rejects_invalid_template(): void
    {
        //! @section Arrange
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid template name: 'invalid'");

        //! @section Act
        TemplateName::fromString('invalid');
    }

    public function test_from_string_rejects_empty_string(): void
    {
        //! @section Arrange
        $this->expectException(\InvalidArgumentException::class);

        //! @section Act
        TemplateName::fromString('');
    }

    public function test_is_valid_rejects_unknown_names(): void
    {
        //! @section Assert
        $this->assertFalse(TemplateName::isValid('invalid'));
        $this->assertFalse(TemplateName::isValid(''));
        $this->assertFalse(TemplateName::isValid('home '));
    }

    public function test_is_error_and_content_template_flags(): void
    {
        //! @section Assert
        $this->assertTrue(TemplateName::NOT_FOUND->isErrorTemplate());
        $this->assertFalse(TemplateName::NOT_FOUND->isContentTemplate());
        $this->assertFalse(TemplateName::HOME->isErrorTemplate());
        $this->assertTrue(TemplateName::HOME->isContentTemplate());
    }

    public function test_to_path_builds_file_path_under_templates_dir(): void
    {
        //! @section Arrange
        $tmp = sys_get_temp_dir() . '/templates_' . uniqid();
        mkdir($tmp, 0777, true);

        try {
            $base = FilePath::fromString($tmp);

            //! @section Act
            $path = TemplateName::DEX->toPath($base);

            //! @section Assert
            $this->assertStringEndsWith('/dex.twig', $path->getValue());
        } finally {
            @rmdir($tmp);
        }
    }

    public function test_ensure_exists_throws_when_template_missing(): void
    {
        //! @section Arrange
        $tmp = sys_get_temp_dir() . '/templates_missing_' . uniqid();
        mkdir($tmp, 0777, true);
        $base = FilePath::fromString($tmp);

        try {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessageMatches('/Template not found:/');

            //! @section Act
            TemplateName::DEX->ensureExists(['dex' => $base]);
        } finally {
            @rmdir($tmp);
        }
    }

    public function test_ensure_exists_succeeds_when_file_present(): void
    {
        //! @section Arrange
        $tmp = sys_get_temp_dir() . '/templates_present_' . uniqid();
        mkdir($tmp, 0777, true);
        $file = $tmp . '/home.twig';
        file_put_contents($file, '{# test #}');
        $base = FilePath::fromString($tmp);

        try {
            //! @section Act
            $path = TemplateName::HOME->ensureExists(['site' => $base]);

            //! @section Assert
            $this->assertTrue($path->isFile());
            $this->assertStringEndsWith('/home.twig', $path->getValue());
        } finally {
            @unlink($file);
            @rmdir($tmp);
        }
    }

    public function test_ensure_exists_rejects_directories_named_like_template(): void
    {
        //! @section Arrange
        $tmp = sys_get_temp_dir() . '/templates_dir_' . uniqid();
        mkdir($tmp, 0777, true);
        mkdir($tmp . '/home.twig', 0777, true);
        $base = FilePath::fromString($tmp);

        try {
            $this->expectException(\RuntimeException::class);

            //! @section Act
            TemplateName::HOME->ensureExists(['site' => $base]);
        } finally {
            @rmdir($tmp . '/home.twig');
            @rmdir($tmp);
        }
    }
}
