<?php

declare(strict_types=1);

namespace Tests\Unit\Cv;

use App\Cv\OrganizationExperience;
use PHPUnit\Framework\TestCase;

final class OrganizationExperienceTest extends TestCase
{
    public function test_parse_accepts_organization_with_optional_location_and_roles(): void
    {
        //! @section Arrange
        $data = [
            'organization' => 'Example Forces',
            'location' => 'Sweden',
            'roles' => [
                [
                    'position' => 'Specialist',
                    'from' => '2018-01',
                    'to' => '2019-06',
                ],
            ],
        ];

        //! @section Act
        $result = OrganizationExperience::parse($data);

        //! @section Assert
        $this->assertTrue($result->isSuccess());
        $entry = $result->getValue();
        $this->assertSame('Example Forces', $entry->organization);
        $this->assertSame('Sweden', $entry->location);
        $this->assertSame('Specialist', $entry->roles[0]->position);
        $this->assertSame(
            [
                'organization' => 'Example Forces',
                'roles' => [
                    [
                        'position' => 'Specialist',
                        'from' => '2018-01',
                        'to' => '2019-06',
                    ],
                ],
                'location' => 'Sweden',
            ],
            $entry->toArray()
        );
    }

    public function test_parse_rejects_missing_organization(): void
    {
        //! @section Act
        $result = OrganizationExperience::parse(['roles' => []]);

        //! @section Assert
        $this->assertTrue($result->isFailure());
        $this->assertStringContainsString('organization', $result->getError());
    }
}
