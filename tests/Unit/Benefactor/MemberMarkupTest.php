<?php

declare(strict_types=1);

namespace Tests\Unit\Benefactor;

use App\Benefactor\MemberMarkup;
use App\Benefactor\PatronMember;
use App\Benefactor\PatronTier;
use PHPUnit\Framework\TestCase;

//! @brief MemberMarkup turns entitled patrons into copyable tier spans
final class MemberMarkupTest extends TestCase
{
    public function test_formats_hardy_and_euclid_example(): void
    {
        //! @section Arrange
        $members = [
            new PatronMember('Epoint Man', 'active_patron', [
                new PatronTier('Hardy Club', 500),
            ]),
            new PatronMember('Question Man', 'active_patron', [
                new PatronTier('Euclid Club', 1000),
            ]),
        ];

        //! @section Act
        $formatted = (new MemberMarkup())->format($members);

        //! @section Assert
        $this->assertSame(
            '<span class="euclid-club">Question Man</span>, <span class="hardy-club">Epoint Man</span>',
            $formatted['markup']
        );
        $this->assertSame(
            [
                ['class' => 'euclid-club', 'name' => 'Question Man'],
                ['class' => 'hardy-club', 'name' => 'Epoint Man'],
            ],
            $formatted['patrons']
        );
    }

    public function test_uses_highest_of_two_entitled_tiers(): void
    {
        //! @section Arrange
        $members = [
            new PatronMember('Dual Patron', 'active_patron', [
                new PatronTier('Hardy Club', 500),
                new PatronTier('Euclid Club', 1000),
            ]),
        ];

        //! @section Act
        $formatted = (new MemberMarkup())->format($members);

        //! @section Assert
        $this->assertSame('<span class="euclid-club">Dual Patron</span>', $formatted['markup']);
        $this->assertSame('euclid-club', $formatted['patrons'][0]['class']);
    }

    public function test_omits_inactive_patrons(): void
    {
        //! @section Arrange
        $members = [
            new PatronMember('Former Fan', 'declined_patron', [
                new PatronTier('Hardy Club', 500),
            ]),
            new PatronMember('Still Here', 'active_patron', [
                new PatronTier('Hardy Club', 500),
            ]),
        ];

        //! @section Act
        $formatted = (new MemberMarkup())->format($members);

        //! @section Assert
        $this->assertSame('<span class="hardy-club">Still Here</span>', $formatted['markup']);
        $this->assertCount(1, $formatted['patrons']);
    }

    public function test_omits_members_with_no_entitled_tier(): void
    {
        //! @section Arrange
        $members = [
            new PatronMember('Free Follower', 'active_patron', []),
        ];

        //! @section Act
        $formatted = (new MemberMarkup())->format($members);

        //! @section Assert
        $this->assertSame('', $formatted['markup']);
        $this->assertSame([], $formatted['patrons']);
    }

    public function test_escapes_names_in_markup(): void
    {
        //! @section Arrange
        $members = [
            new PatronMember('<script>alert(1)</script>', 'active_patron', [
                new PatronTier('Hardy Club', 500),
            ]),
        ];

        //! @section Act
        $formatted = (new MemberMarkup())->format($members);

        //! @section Assert
        $this->assertSame(
            '<span class="hardy-club">&lt;script&gt;alert(1)&lt;/script&gt;</span>',
            $formatted['markup']
        );
        $this->assertSame('<script>alert(1)</script>', $formatted['patrons'][0]['name']);
    }

    public function test_empty_list_returns_empty_markup(): void
    {
        //! @section Arrange
        $members = [];

        //! @section Act
        $formatted = (new MemberMarkup())->format($members);

        //! @section Assert
        $this->assertSame('', $formatted['markup']);
        $this->assertSame([], $formatted['patrons']);
    }

    public function test_sorts_equal_tiers_by_name(): void
    {
        //! @section Arrange
        $members = [
            new PatronMember('Zed', 'active_patron', [new PatronTier('Hardy Club', 500)]),
            new PatronMember('Ann', 'active_patron', [new PatronTier('Hardy Club', 500)]),
        ];

        //! @section Act
        $formatted = (new MemberMarkup())->format($members);

        //! @section Assert
        $this->assertSame(
            '<span class="hardy-club">Ann</span>, <span class="hardy-club">Zed</span>',
            $formatted['markup']
        );
    }
}
