<?php

declare(strict_types=1);

namespace Tests\Unit\Cv;

use App\Cv\CvLabels;
use PHPUnit\Framework\TestCase;

final class CvLabelsTest extends TestCase
{
    public function test_english_labels_include_section_titles(): void
    {
        // Arrange / Act
        $labels = CvLabels::forLanguage('en');

        // Assert
        $this->assertSame('Experience', $labels['experience']);
        $this->assertSame('Education', $labels['education']);
        $this->assertSame('Skills', $labels['skills']);
        $this->assertSame('Languages', $labels['languages']);
        $this->assertSame('Certificates', $labels['certificates']);
        $this->assertSame('Present', $labels['present']);
        $this->assertSame('Grade', $labels['grade']);
        $this->assertSame('web embedded', $labels['skill_groups']['web_embedded']);
        $this->assertSame('development', $labels['skill_groups']['systems_development']);
        $this->assertSame('communication', $labels['skill_groups']['communication']);
    }

    public function test_swedish_labels_include_section_titles(): void
    {
        // Arrange / Act
        $labels = CvLabels::forLanguage('sv');

        // Assert
        $this->assertSame('Erfarenhet', $labels['experience']);
        $this->assertSame('Utbildning', $labels['education']);
        $this->assertSame('Kompetens', $labels['skills']);
        $this->assertSame('Språk', $labels['languages']);
        $this->assertSame('Certifikat', $labels['certificates']);
        $this->assertSame('Nuvarande', $labels['present']);
        $this->assertSame('Betyg', $labels['grade']);
        $this->assertSame('webb och inbyggda system', $labels['skill_groups']['web_embedded']);
        $this->assertSame('utveckling', $labels['skill_groups']['systems_development']);
    }
}
