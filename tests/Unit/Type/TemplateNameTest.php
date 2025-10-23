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
        //! @section Act
        $homeValue = TemplateName::HOME->value;
        $dexValue = TemplateName::DEX->value;
        $articleValue = TemplateName::ARTICLE->value;
        $notFoundValue = TemplateName::NOT_FOUND->value;

        //! @section Assert
        $this->assertSame('home', $homeValue);
        $this->assertSame('dex', $dexValue);
        $this->assertSame('article', $articleValue);
        $this->assertSame('404', $notFoundValue);
    }

    public function test_from_string_with_valid_templates(): void
    {
        //! @section Act
        $homeTemplate = TemplateName::fromString('home');
        $dexTemplate = TemplateName::fromString('dex');
        $articleTemplate = TemplateName::fromString('article');
        $notFoundTemplate = TemplateName::fromString('404');

        //! @section Assert
        $this->assertSame(TemplateName::HOME, $homeTemplate);
        $this->assertSame(TemplateName::DEX, $dexTemplate);
        $this->assertSame(TemplateName::ARTICLE, $articleTemplate);
        $this->assertSame(TemplateName::NOT_FOUND, $notFoundTemplate);
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
        //! @section Act
        $homeValid = TemplateName::isValid('home');
        $dexValid = TemplateName::isValid('dex');
        $articleValid = TemplateName::isValid('article');
        $notFoundValid = TemplateName::isValid('404');

        //! @section Assert
        $this->assertTrue($homeValid);
        $this->assertTrue($dexValid);
        $this->assertTrue($articleValid);
        $this->assertTrue($notFoundValid);
    }

    public function test_is_valid_with_invalid_templates(): void
    {
        //! @section Act
        $invalidValid = TemplateName::isValid('invalid');
        $emptyValid = TemplateName::isValid('');
        $homValid = TemplateName::isValid('hom');
        $homeSpaceValid = TemplateName::isValid('home ');

        //! @section Assert
        $this->assertFalse($invalidValid);
        $this->assertFalse($emptyValid);
        $this->assertFalse($homValid);
        $this->assertFalse($homeSpaceValid);
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
        //! @section Act
        $homeDescription = TemplateName::HOME->getDescription();
        $dexDescription = TemplateName::DEX->getDescription();

        //! @section Assert
        $this->assertSame('Home page template', $homeDescription);
        $this->assertSame('Pokemon dex detail page template', $dexDescription);

        //! @section Act
        $articleDescription = TemplateName::ARTICLE->getDescription();
        $notFoundDescription = TemplateName::NOT_FOUND->getDescription();

        //! @section Assert
        $this->assertSame('Article/blog post template', $articleDescription);
        $this->assertSame('404 error page template', $notFoundDescription);
    }

    public function test_is_error_template(): void
    {
        //! @section Act
        $homeIsError = TemplateName::HOME->isErrorTemplate();
        $dexIsError = TemplateName::DEX->isErrorTemplate();
        $articleIsError = TemplateName::ARTICLE->isErrorTemplate();
        $notFoundIsError = TemplateName::NOT_FOUND->isErrorTemplate();

        //! @section Assert
        $this->assertFalse($homeIsError);
        $this->assertFalse($dexIsError);
        $this->assertFalse($articleIsError);
        $this->assertTrue($notFoundIsError);
    }

    public function test_is_content_template(): void
    {
        //! @section Act
        $homeIsContent = TemplateName::HOME->isContentTemplate();
        $dexIsContent = TemplateName::DEX->isContentTemplate();
        $articleIsContent = TemplateName::ARTICLE->isContentTemplate();
        $notFoundIsContent = TemplateName::NOT_FOUND->isContentTemplate();

        //! @section Assert
        $this->assertTrue($homeIsContent);
        $this->assertTrue($dexIsContent);
        $this->assertTrue($articleIsContent);
        $this->assertFalse($notFoundIsContent);
    }

    public function test_to_string(): void
    {
        //! @section Act
        $homeString = TemplateName::HOME->toString();
        $dexString = TemplateName::DEX->toString();
        $articleString = TemplateName::ARTICLE->toString();
        $notFoundString = TemplateName::NOT_FOUND->toString();

        //! @section Assert
        $this->assertSame('home', $homeString);
        $this->assertSame('dex', $dexString);
        $this->assertSame('article', $articleString);
        $this->assertSame('404', $notFoundString);
    }

    public function test_enum_comparison(): void
    {
        //! @section Arrange
        $template1 = TemplateName::HOME;
        $template2 = TemplateName::HOME;
        $template3 = TemplateName::DEX;

        //! @section Act
        $template1EqualsTemplate2 = $template1 === $template2;
        $template1EqualsTemplate3 = $template1 === $template3;

        //! @section Assert
        $this->assertSame($template1, $template2);
        $this->assertNotSame($template1, $template3);
        $this->assertTrue($template1EqualsTemplate2);
        $this->assertFalse($template1EqualsTemplate3);
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

        //! @section Act
        $homeHasKey = array_key_exists(TemplateName::HOME->value, $templates);
        $dexHasKey = array_key_exists(TemplateName::DEX->value, $templates);
        $articleHasKey = array_key_exists(TemplateName::ARTICLE->value, $templates);
        $notFoundHasKey = array_key_exists(TemplateName::NOT_FOUND->value, $templates);

        $homeValue = $templates[TemplateName::HOME->value];
        $dexValue = $templates[TemplateName::DEX->value];
        $articleValue = $templates[TemplateName::ARTICLE->value];
        $notFoundValue = $templates[TemplateName::NOT_FOUND->value];

        //! @section Assert
        $this->assertTrue($homeHasKey);
        $this->assertTrue($dexHasKey);
        $this->assertTrue($articleHasKey);
        $this->assertTrue($notFoundHasKey);

        $this->assertSame('Home template', $homeValue);
        $this->assertSame('Dex template', $dexValue);
        $this->assertSame('Article template', $articleValue);
        $this->assertSame('404 template', $notFoundValue);
    }

    public function test_to_twig_path_returns_filename_with_extension(): void
    {
        //! @section Act
        $homeTwigPath = TemplateName::HOME->toTwigPath();
        $dexTwigPath = TemplateName::DEX->toTwigPath();
        $articleTwigPath = TemplateName::ARTICLE->toTwigPath();
        $notFoundTwigPath = TemplateName::NOT_FOUND->toTwigPath();

        //! @section Assert
        $this->assertSame('home.twig', $homeTwigPath);
        $this->assertSame('dex.twig', $dexTwigPath);
        $this->assertSame('article.twig', $articleTwigPath);
        $this->assertSame('404.twig', $notFoundTwigPath);
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
