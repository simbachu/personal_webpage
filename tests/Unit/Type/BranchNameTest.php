<?php

declare(strict_types=1);

namespace Tests\Unit\Type;

use PHPUnit\Framework\TestCase;
use App\Type\BranchName;

//! @brief Test suite for the BranchName value object
class BranchNameTest extends TestCase
{
    public function test_branch_name_accepts_valid_names(): void
    {
        //! @section Arrange
        $branch = BranchName::fromString('main');

        //! @section Act & Assert
        $this->assertSame('main', $branch->getValue());
        $this->assertSame('main', (string) $branch);
    }

    public function test_branch_name_accepts_common_branch_names(): void
    {
        //! @section Arrange
        $branches = ['main', 'develop', 'developing', 'feature/new-feature', 'hotfix/bug-fix'];

        //! @section Act & Assert
        foreach ($branches as $branchName) {
            $branch = BranchName::fromString($branchName);
            $this->assertSame($branchName, $branch->getValue());
        }
    }

    public function test_branch_name_accepts_names_with_hyphens_and_underscores(): void
    {
        //! @section Arrange & Act
        $branch = BranchName::fromString('feature-my-feature');

        //! @section Assert
        $this->assertSame('feature-my-feature', $branch->getValue());
    }

    public function test_branch_name_rejects_empty_string(): void
    {
        //! @section Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Branch name cannot be empty');

        //! @section Act
        BranchName::fromString('');
    }

    public function test_branch_name_rejects_whitespace_only(): void
    {
        //! @section Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Branch name cannot be empty');

        //! @section Act
        BranchName::fromString('   ');
    }

    public function test_branch_name_rejects_spaces(): void
    {
        //! @section Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Branch name cannot contain spaces, tilde, caret, colon, question mark, asterisk, or brackets');

        //! @section Act
        BranchName::fromString('my branch');
    }

    public function test_branch_name_rejects_invalid_characters(): void
    {
        //! @section Arrange
        $invalidChars = ['~', '^', ':', '?', '*', '[', ']'];

        //! @section Act & Assert
        foreach ($invalidChars as $char) {
            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage('Branch name cannot contain spaces, tilde, caret, colon, question mark, asterisk, or brackets');

            BranchName::fromString('branch' . $char);

            // Reset exception expectation for next iteration
            $this->expectException(\InvalidArgumentException::class);
        }
    }

    public function test_branch_name_rejects_starting_with_dot(): void
    {
        //! @section Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Branch name cannot start with a dot');

        //! @section Act
        BranchName::fromString('.hidden');
    }

    public function test_branch_name_rejects_ending_with_dot(): void
    {
        //! @section Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Branch name cannot end with a dot or slash');

        //! @section Act
        BranchName::fromString('branch.');
    }

    public function test_branch_name_rejects_ending_with_slash(): void
    {
        //! @section Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Branch name cannot end with a dot or slash');

        //! @section Act
        BranchName::fromString('branch/');
    }

    public function test_branch_name_rejects_too_long(): void
    {
        //! @section Arrange
        $longName = str_repeat('a', 101);

        //! @section Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Branch name cannot exceed 100 characters');

        //! @section Act
        BranchName::fromString($longName);
    }

    public function test_branch_name_rejects_reserved_names(): void
    {
        //! @section Arrange
        $reserved = ['HEAD', 'head', 'HEAD/', 'head/'];

        //! @section Act & Assert
        foreach ($reserved as $reservedName) {
            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage('Branch name cannot be a reserved Git name');

            BranchName::fromString($reservedName);

            // Reset exception expectation for next iteration
            $this->expectException(\InvalidArgumentException::class);
        }
    }

    public function test_branch_name_equality(): void
    {
        //! @section Arrange
        $branch1 = BranchName::fromString('main');
        $branch2 = BranchName::fromString('main');
        $branch3 = BranchName::fromString('develop');

        //! @section Act & Assert
        $this->assertTrue($branch1->equals($branch2));
        $this->assertFalse($branch1->equals($branch3));
    }

    public function test_branch_name_trims_whitespace(): void
    {
        //! @section Arrange & Act
        $branch = BranchName::fromString('  main  ');

        //! @section Assert
        $this->assertSame('main', $branch->getValue());
    }
}
