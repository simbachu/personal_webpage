<?php

declare(strict_types=1);

namespace Tests\Unit\Type;

use PHPUnit\Framework\TestCase;
use App\Type\MonsterIdentifier;
use App\Type\RepositoryIdentifier;

//! @brief Test suite for the generic Identifier type and its implementations
class IdentifierTest extends TestCase
{
    public function test_monster_identifier_accepts_valid_names(): void
    {
        //! @section Arrange
        $identifier = MonsterIdentifier::fromString('pikachu');

        //! @section Act & Assert
        $this->assertSame('pikachu', $identifier->getValue());
        $this->assertSame('pikachu', (string) $identifier);
        $this->assertTrue($identifier->isName());
        $this->assertFalse($identifier->isNumeric());
        $this->assertSame('pikachu', $identifier->getName());
    }

    public function test_monster_identifier_accepts_valid_numeric_ids(): void
    {
        //! @section Arrange
        $identifier = MonsterIdentifier::fromString('25');

        //! @section Act & Assert
        $this->assertSame('25', $identifier->getValue());
        $this->assertTrue($identifier->isNumeric());
        $this->assertFalse($identifier->isName());
        $this->assertSame(25, $identifier->getNumericId());
    }

    public function test_monster_identifier_accepts_names_with_hyphens_and_underscores(): void
    {
        //! @section Arrange & Act
        $identifier = MonsterIdentifier::fromString('ho-oh');

        //! @section Assert
        $this->assertSame('ho-oh', $identifier->getValue());
        $this->assertTrue($identifier->isName());
    }

    public function test_monster_identifier_rejects_empty_string(): void
    {
        //! @section Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Identifier cannot be empty');

        //! @section Act
        MonsterIdentifier::fromString('');
    }

    public function test_monster_identifier_rejects_invalid_characters(): void
    {
        //! @section Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Monster identifier must contain only alphanumeric characters, hyphens, and underscores');

        //! @section Act
        MonsterIdentifier::fromString('pika@chu');
    }

    public function test_monster_identifier_rejects_too_long_names(): void
    {
        //! @section Arrange
        $longName = str_repeat('a', 51);

        //! @section Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Monster identifier cannot exceed 50 characters');

        //! @section Act
        MonsterIdentifier::fromString($longName);
    }

    public function test_monster_identifier_factory_methods(): void
    {
        //! @section Act
        $numericId = MonsterIdentifier::fromNumericId(25);
        $nameId = MonsterIdentifier::fromName('pikachu');

        //! @section Assert
        $this->assertSame('25', $numericId->getValue());
        $this->assertSame(25, $numericId->getNumericId());
        $this->assertSame('pikachu', $nameId->getValue());
        $this->assertSame('pikachu', $nameId->getName());
    }

    public function test_monster_identifier_rejects_non_positive_numeric_id(): void
    {
        //! @section Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Monster ID must be positive');

        //! @section Act
        MonsterIdentifier::fromNumericId(0);
    }

    public function test_monster_identifier_equality(): void
    {
        //! @section Arrange
        $id1 = MonsterIdentifier::fromString('pikachu');
        $id2 = MonsterIdentifier::fromString('pikachu');
        $id3 = MonsterIdentifier::fromString('raichu');

        //! @section Act & Assert
        $this->assertTrue($id1->equals($id2));
        $this->assertFalse($id1->equals($id3));
    }

    public function test_repository_identifier_accepts_valid_format(): void
    {
        //! @section Arrange
        $identifier = RepositoryIdentifier::fromString('simbachu/personal_webpage');

        //! @section Act & Assert
        $this->assertSame('simbachu/personal_webpage', $identifier->getValue());
        $this->assertSame('simbachu', $identifier->getOwner());
        $this->assertSame('personal_webpage', $identifier->getRepository());
    }

    public function test_repository_identifier_accepts_names_with_hyphens_dots_underscores(): void
    {
        //! @section Arrange & Act
        $identifier = RepositoryIdentifier::fromString('my-org/project.name_v2');

        //! @section Assert
        $this->assertSame('my-org', $identifier->getOwner());
        $this->assertSame('project.name_v2', $identifier->getRepository());
    }

    public function test_repository_identifier_rejects_missing_slash(): void
    {
        //! @section Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Repository identifier must contain exactly one slash');

        //! @section Act
        RepositoryIdentifier::fromString('simbachu');
    }

    public function test_repository_identifier_rejects_multiple_slashes(): void
    {
        //! @section Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Repository name must contain only alphanumeric characters, hyphens, underscores, and dots');

        //! @section Act
        RepositoryIdentifier::fromString('simbachu/repo/subdir');
    }

    public function test_repository_identifier_rejects_invalid_characters(): void
    {
        //! @section Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Repository owner must contain only alphanumeric characters, hyphens, underscores, and dots');

        //! @section Act
        RepositoryIdentifier::fromString('sim@bachu/repo');
    }

    public function test_repository_identifier_factory_method(): void
    {
        //! @section Act
        $identifier = RepositoryIdentifier::fromOwnerAndRepository('owner', 'repo');

        //! @section Assert
        $this->assertSame('owner/repo', $identifier->getValue());
        $this->assertSame('owner', $identifier->getOwner());
        $this->assertSame('repo', $identifier->getRepository());
    }
}
