<?php

declare(strict_types=1);

namespace Tests\Unit\Cv;

use App\Cv\CertificateEntry;
use App\Cv\CvDocument;
use App\Cv\CvLoader;
use App\Cv\EducationEntry;
use App\Cv\EmployerExperience;
use App\Cv\LanguageProficiency;
use App\Shared\Support\FilePath;
use PHPUnit\Framework\TestCase;

//! @brief Unit tests for CvLoader parsing into typed CvDocument
final class CvLoaderTest extends TestCase
{
    private string $testContentPath;

    protected function setUp(): void
    {
        $this->testContentPath = sys_get_temp_dir() . '/test_cv_' . uniqid();
        mkdir($this->testContentPath);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->testContentPath)) {
            $files = glob($this->testContentPath . '/*') ?: [];
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->testContentPath);
        }
    }

    public function test_loads_and_parses_language_section_with_root_contact_fields(): void
    {
        // Arrange
        $this->writeCvJson($this->minimalCvDocument());
        $loader = new CvLoader(FilePath::fromString($this->testContentPath . '/cv.json'));

        // Act
        $cv = $loader->load('en');

        // Assert
        $this->assertInstanceOf(CvDocument::class, $cv);
        $this->assertSame('Jennifer Jonathan Gott', $cv->name);
        $this->assertSame('simbachu@gmail.com', $cv->email);
        $this->assertSame('+46 704 91 10 97', $cv->phone);
        $this->assertSame('https://www.simbachu.com', $cv->website);
        $this->assertSame('https://www.linkedin.com/in/example/', $cv->linkedin);
        $this->assertSame('https://github.com/simbachu', $cv->github);
        $this->assertSame('en', $cv->language);
        $this->assertSame('Systems developer.', $cv->summary);
        $this->assertInstanceOf(EducationEntry::class, $cv->education[0]);
        $this->assertSame('Chas Academy', $cv->education[0]->institution);
        $this->assertInstanceOf(CertificateEntry::class, $cv->certificates[0]);
        $this->assertSame('C1 Advanced', $cv->certificates[0]->name);
        $this->assertInstanceOf(EmployerExperience::class, $cv->experience[0]);
        $this->assertSame('Berg Propulsion', $cv->experience[0]->company);
        $this->assertInstanceOf(LanguageProficiency::class, $cv->languages[0]);
        $this->assertSame('Swedish', $cv->languages[0]->language);
        $this->assertSame(['C', 'C++'], $cv->skills['programming_languages']);
        $this->assertSame('CI/CD with GitHub Actions.', $cv->skillHighlights[0]);

        $asArray = $cv->toArray();
        $this->assertArrayNotHasKey('lang-en', $asArray);
        $this->assertArrayNotHasKey('lang-sv', $asArray);
    }

    public function test_loads_requested_language_section(): void
    {
        // Arrange
        $this->writeCvJson($this->minimalCvDocument());
        $loader = new CvLoader(FilePath::fromString($this->testContentPath . '/cv.json'));

        // Act
        $cv = $loader->load('sv');

        // Assert
        $this->assertSame('sv', $cv->language);
        $this->assertSame('Systemutvecklare.', $cv->summary);
        $this->assertSame('Jennifer Jonathan Gott', $cv->name);
    }

    public function test_throws_when_file_is_missing(): void
    {
        // Arrange
        $loader = new CvLoader(FilePath::fromString($this->testContentPath . '/missing.json'));

        // Act & Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/does not exist|not readable|Failed to read/i');
        $loader->load('en');
    }

    public function test_throws_when_language_section_is_missing(): void
    {
        // Arrange
        $document = $this->minimalCvDocument();
        unset($document['lang-sv']);
        $this->writeCvJson($document);
        $loader = new CvLoader(FilePath::fromString($this->testContentPath . '/cv.json'));

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('lang-de');
        $loader->load('de');
    }

    public function test_throws_when_json_is_invalid(): void
    {
        // Arrange
        file_put_contents($this->testContentPath . '/cv.json', '{not valid json');
        $loader = new CvLoader(FilePath::fromString($this->testContentPath . '/cv.json'));

        // Act & Assert
        $this->expectException(\JsonException::class);
        $loader->load('en');
    }

    public function test_throws_when_required_education_fields_are_missing(): void
    {
        // Arrange
        $document = $this->minimalCvDocument();
        $document['lang-en']['education'] = [
            ['institution' => 'Chas Academy'],
        ];
        $this->writeCvJson($document);
        $loader = new CvLoader(FilePath::fromString($this->testContentPath . '/cv.json'));

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/program|Education/');
        $loader->load('en');
    }

    public function test_throws_when_experience_entry_has_no_discriminator(): void
    {
        // Arrange
        $document = $this->minimalCvDocument();
        $document['lang-en']['experience'] = [
            ['location' => 'Gothenburg'],
        ];
        $this->writeCvJson($document);
        $loader = new CvLoader(FilePath::fromString($this->testContentPath . '/cv.json'));

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/company, organization, or section/');
        $loader->load('en');
    }

    public function test_loads_organization_experience_entries(): void
    {
        // Arrange
        $document = $this->minimalCvDocument();
        $document['lang-en']['experience'] = [
            [
                'organization' => 'Example Forces',
                'location' => 'Sweden',
                'roles' => [
                    [
                        'position' => 'Specialist',
                        'from' => '2018-01',
                        'to' => '2019-06',
                    ],
                ],
            ],
        ];
        $this->writeCvJson($document);
        $loader = new CvLoader(FilePath::fromString($this->testContentPath . '/cv.json'));

        // Act
        $cv = $loader->load('en');

        // Assert
        $this->assertInstanceOf(\App\Cv\OrganizationExperience::class, $cv->experience[0]);
        $this->assertSame('Example Forces', $cv->experience[0]->organization);
        $this->assertSame('Sweden', $cv->experience[0]->location);
        $this->assertSame('Specialist', $cv->experience[0]->roles[0]->position);
    }

    //! @brief Minimal valid multi-language CV JSON for loader tests
    //! @return array<string, mixed>
    private function minimalCvDocument(): array
    {
        return [
            'name' => 'Jennifer Jonathan Gott',
            'email' => 'simbachu@gmail.com',
            'phone' => '+46 704 91 10 97',
            'website' => 'https://www.simbachu.com',
            'linkedin' => 'https://www.linkedin.com/in/example/',
            'github' => 'https://github.com/simbachu',
            'lang-en' => [
                'summary' => 'Systems developer.',
                'education' => [
                    [
                        'institution' => 'Chas Academy',
                        'program' => 'Systems Developer C/C++',
                        'from' => '2024-09',
                        'to' => '2026-06',
                    ],
                ],
                'certificates' => [
                    [
                        'name' => 'C1 Advanced',
                        'issuer' => 'Cambridge English',
                        'issued' => '2005-06',
                    ],
                ],
                'experience' => [
                    [
                        'company' => 'Berg Propulsion',
                        'roles' => [],
                    ],
                ],
                'languages' => [
                    [
                        'language' => 'Swedish',
                        'level' => 'Native',
                    ],
                ],
                'skills' => [
                    'programming_languages' => ['C', 'C++'],
                ],
                'skill_highlights' => [
                    'CI/CD with GitHub Actions.',
                ],
            ],
            'lang-sv' => [
                'summary' => 'Systemutvecklare.',
                'education' => [],
                'certificates' => [],
                'experience' => [],
                'languages' => [],
                'skills' => new \stdClass(),
                'skill_highlights' => [],
            ],
        ];
    }

    //! @brief Write a CV JSON fixture into the temp content directory
    //! @param data Associative array to encode as JSON
    private function writeCvJson(array $data): void
    {
        file_put_contents(
            $this->testContentPath . '/cv.json',
            json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT)
        );
    }
}
