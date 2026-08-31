<?php

declare(strict_types=1);

namespace Tests\Unit\Benefactor;

use PHPUnit\Framework\TestCase;
use Tests\Support\TwigTestFactory;

//! @brief Benefactor template renders login and copyable member markup
final class BenefactorTemplateTest extends TestCase
{
    public function test_login_view_renders_patreon_link(): void
    {
        //! @section Arrange
        $twig = TwigTestFactory::createEnvironment(['strict_variables' => true]);
        $data = array_merge(TwigTestFactory::layoutGlobals(), [
            'configured' => true,
            'logged_in' => false,
            'authorize_url' => 'https://www.patreon.com/oauth2/authorize?state=abc',
            'markup' => null,
            'patrons' => [],
            'error' => null,
            'meta' => ['title' => 'Benefactor'],
        ]);

        //! @section Act
        $html = $twig->render('@benefactor/benefactor.twig', $data);

        //! @section Assert
        $this->assertStringContainsString('Log in with Patreon', $html);
        $this->assertStringContainsString('https://www.patreon.com/oauth2/authorize?state=abc', $html);
        $this->assertStringNotContainsString('benefactor-markup', $html);
    }

    public function test_logged_in_view_renders_copyable_textarea_and_preview(): void
    {
        //! @section Arrange
        $twig = TwigTestFactory::createEnvironment(['strict_variables' => true]);
        $data = array_merge(TwigTestFactory::layoutGlobals(), [
            'configured' => true,
            'logged_in' => true,
            'authorize_url' => null,
            'markup' => '<span class="hardy-club">Epoint Man</span>, <span class="euclid-club">Question Man</span>',
            'patrons' => [
                ['class' => 'hardy-club', 'name' => 'Epoint Man'],
                ['class' => 'euclid-club', 'name' => 'Question Man'],
            ],
            'error' => null,
            'meta' => ['title' => 'Benefactor'],
        ]);

        //! @section Act
        $html = $twig->render('@benefactor/benefactor.twig', $data);

        //! @section Assert
        $this->assertStringContainsString('<textarea', $html);
        $this->assertStringContainsString('&lt;span class=&quot;hardy-club&quot;&gt;Epoint Man&lt;/span&gt;', $html);
        $this->assertStringContainsString('<span class="hardy-club">Epoint Man</span>', $html);
        $this->assertStringContainsString('<span class="euclid-club">Question Man</span>', $html);
        $this->assertStringNotContainsString('benefactor-login', $html);
    }
}
