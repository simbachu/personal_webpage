<?php

declare(strict_types=1);

namespace Tests\Unit\Type;

use PHPUnit\Framework\TestCase;
use App\Type\TemplateName;

//! @brief Test suite for the TemplateName enum
class TemplateNameTest extends TestCase
{
    public function test_all_template_names_are_defined(): void
    {
        //! @section Act
        $templates = TemplateName::cases();

        //! @section Assert
        $this->assertCount(4, $templates);

        $expectedTemplates = ['home', 'dex', 'article', '404'];
        $actualTemplates = array_column($templates, 'value');

        foreach ($expectedTemplates as $expected) {
            $this->assertContains($expected, $actualTemplates, "Template '{$expected}' should be defined");
        }
    }

    public function test_template_name_values_are_correct(): void
    {
        //! @section Act & Assert
        $this->assertSame('home', TemplateName::HOME->value);
        $this->assertSame('dex', TemplateName::DEX->value);
        $this->assertSame('article', TemplateName::ARTICLE->value);
        $this->assertSame('404', TemplateName::NOT_FOUND->value);
    }

    public function test_from_string_with_valid_templates(): void
    {
        //! @section Act & Assert
        $this->assertSame(TemplateName::HOME, TemplateName::fromString('home'));
        $this->assertSame(TemplateName::DEX, TemplateName::fromString('dex'));
        $this->assertSame(TemplateName::ARTICLE, TemplateName::fromString('article'));
        $this->assertSame(TemplateName::NOT_FOUND, TemplateName::fromString('404'));
    }

    public function test_from_string_with_invalid_template(): void
    {
        //! @section Arrange
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid template name: \'invalid\'. Valid templates are: home, dex, article, 404');

        //! @section Act
        TemplateName::fromString('invalid');
    }

    public function test_from_string_with_empty_string(): void
    {
        //! @section Arrange
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid template name: \'\'. Valid templates are: home, dex, article, 404');

        //! @section Act
        TemplateName::fromString('');
    }

    public function test_is_valid_with_valid_templates(): void
    {
        //! @section Act & Assert
        $this->assertTrue(TemplateName::isValid('home'));
        $this->assertTrue(TemplateName::isValid('dex'));
        $this->assertTrue(TemplateName::isValid('article'));
        $this->assertTrue(TemplateName::isValid('404'));
    }

    public function test_is_valid_with_invalid_templates(): void
    {
        //! @section Act & Assert
        $this->assertFalse(TemplateName::isValid('invalid'));
        $this->assertFalse(TemplateName::isValid(''));
        $this->assertFalse(TemplateName::isValid('hom'));
        $this->assertFalse(TemplateName::isValid('home '));
        $this->assertFalse(TemplateName::isValid(' HOME'));
    }

    public function test_get_all_values(): void
    {
        //! @section Act
        $allValues = TemplateName::getAllValues();

        //! @section Assert
        $this->assertIsArray($allValues);
        $this->assertCount(4, $allValues);
        $this->assertContains('home', $allValues);
        $this->assertContains('dex', $allValues);
        $this->assertContains('article', $allValues);
        $this->assertContains('404', $allValues);
        $this->assertSame(['home', 'dex', 'article', '404'], $allValues);
    }

    public function test_get_description(): void
    {
        //! @section Act & Assert
        $this->assertSame('Home page template', TemplateName::HOME->getDescription());
        $this->assertSame('Pokemon dex detail page template', TemplateName::DEX->getDescription());
        $this->assertSame('Article/blog post template', TemplateName::ARTICLE->getDescription());
        $this->assertSame('404 error page template', TemplateName::NOT_FOUND->getDescription());
    }

    public function test_is_error_template(): void
    {
        //! @section Act & Assert
        $this->assertFalse(TemplateName::HOME->isErrorTemplate());
        $this->assertFalse(TemplateName::DEX->isErrorTemplate());
        $this->assertFalse(TemplateName::ARTICLE->isErrorTemplate());
        $this->assertTrue(TemplateName::NOT_FOUND->isErrorTemplate());
    }

    public function test_is_content_template(): void
    {
        //! @section Act & Assert
        $this->assertTrue(TemplateName::HOME->isContentTemplate());
        $this->assertTrue(TemplateName::DEX->isContentTemplate());
        $this->assertTrue(TemplateName::ARTICLE->isContentTemplate());
        $this->assertFalse(TemplateName::NOT_FOUND->isContentTemplate());
    }

    public function test_to_string(): void
    {
        //! @section Act & Assert
        $this->assertSame('home', TemplateName::HOME->toString());
        $this->assertSame('dex', TemplateName::DEX->toString());
        $this->assertSame('article', TemplateName::ARTICLE->toString());
        $this->assertSame('404', TemplateName::NOT_FOUND->toString());
    }

    public function test_enum_comparison(): void
    {
        //! @section Arrange
        $template1 = TemplateName::HOME;
        $template2 = TemplateName::HOME;
        $template3 = TemplateName::DEX;

        //! @section Act & Assert
        $this->assertSame($template1, $template2);
        $this->assertNotSame($template1, $template3);
        $this->assertTrue($template1 === $template2);
        $this->assertFalse($template1 === $template3);
    }

    public function test_enum_can_be_used_in_match_statements(): void
    {
        //! @section Arrange
        $template = TemplateName::DEX;

        //! @section Act
        $result = match ($template) {
            TemplateName::HOME => 'homepage',
            TemplateName::DEX => 'pokemon_page',
            TemplateName::ARTICLE => 'article_page',
            TemplateName::NOT_FOUND => 'error_page',
        };

        //! @section Assert
        $this->assertSame('pokemon_page', $result);
    }

    public function test_enum_can_be_used_in_switch_statements(): void
    {
        //! @section Arrange
        $template = TemplateName::NOT_FOUND;

        //! @section Act
        $result = match ($template) {
            TemplateName::HOME, TemplateName::DEX, TemplateName::ARTICLE => 'content',
            TemplateName::NOT_FOUND => 'error',
        };

        //! @section Assert
        $this->assertSame('error', $result);
    }

    public function test_enum_can_be_serialized(): void
    {
        //! @section Arrange
        $template = TemplateName::DEX;

        //! @section Act
        $serialized = serialize($template);
        $unserialized = unserialize($serialized);

        //! @section Assert
        $this->assertSame($template, $unserialized);
        $this->assertSame('dex', $unserialized->value);
    }

    public function test_enum_can_be_used_in_array_keys(): void
    {
        //! @section Arrange
        $templates = [
            TemplateName::HOME->value => 'Home template',
            TemplateName::DEX->value => 'Dex template',
            TemplateName::ARTICLE->value => 'Article template',
            TemplateName::NOT_FOUND->value => '404 template',
        ];

        //! @section Act & Assert
        $this->assertArrayHasKey(TemplateName::HOME->value, $templates);
        $this->assertArrayHasKey(TemplateName::DEX->value, $templates);
        $this->assertArrayHasKey(TemplateName::ARTICLE->value, $templates);
        $this->assertArrayHasKey(TemplateName::NOT_FOUND->value, $templates);
        $this->assertSame('Home template', $templates[TemplateName::HOME->value]);
        $this->assertSame('Dex template', $templates[TemplateName::DEX->value]);
        $this->assertSame('Article template', $templates[TemplateName::ARTICLE->value]);
        $this->assertSame('404 template', $templates[TemplateName::NOT_FOUND->value]);
    }

    public function test_to_twig_path_returns_filename_with_extension(): void
    {
        //! @section Act & Assert
        $this->assertSame('home.twig', TemplateName::HOME->toTwigPath());
        $this->assertSame('dex.twig', TemplateName::DEX->toTwigPath());
        $this->assertSame('article.twig', TemplateName::ARTICLE->toTwigPath());
        $this->assertSame('404.twig', TemplateName::NOT_FOUND->toTwigPath());
    }

    public function test_to_path_builds_file_path_under_templates_dir(): void
    {
        //! @section Arrange
        $tmp = sys_get_temp_dir() . '/templates_' . uniqid();
        @mkdir($tmp, 0777, true);

        try {
            $base = \App\Type\FilePath::fromString($tmp);

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
        @mkdir($tmp, 0777, true);
        $base = \App\Type\FilePath::fromString($tmp);

        try {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessageMatches('/Template not found:/');

            //! @section Act
            TemplateName::DEX->ensureExists($base);
        } finally {
            @rmdir($tmp);
        }
    }

    public function test_ensure_exists_succeeds_when_file_present(): void
    {
        //! @section Arrange
        $tmp = sys_get_temp_dir() . '/templates_present_' . uniqid();
        @mkdir($tmp, 0777, true);
        $file = $tmp . '/home.twig';
        file_put_contents($file, '{# test #}');
        $base = \App\Type\FilePath::fromString($tmp);

        try {
            //! @section Act
            $path = TemplateName::HOME->ensureExists($base);

            //! @section Assert
            $this->assertTrue($path->exists());
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
        $tmp = sys_get_temp_dir() . '/templates_dir_conflict_' . uniqid();
        @mkdir($tmp, 0777, true);
        // Create a directory with the name '404.twig'
        @mkdir($tmp . '/404.twig', 0777, true);
        $base = \App\Type\FilePath::fromString($tmp);

        try {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessageMatches('/Template not found:/');

            //! @section Act
            TemplateName::NOT_FOUND->ensureExists($base);
        } finally {
            // Cleanup
            @rmdir($tmp . '/404.twig');
            @rmdir($tmp);
        }
    }
}
