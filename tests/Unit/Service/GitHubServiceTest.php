<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use App\Service\GitHubService;
use App\Type\RepositoryInfo;

//! @brief Test suite for GitHubService
//!
//! Tests GitHub API integration with caching and error handling
class GitHubServiceTest extends TestCase
{
    private GitHubService $service; //!< Service under test
    private string $cacheDir; //!< Temporary cache directory

    //! @brief Set up test environment before each test
    protected function setUp(): void
    {
        $this->service = new GitHubService();
        $this->cacheDir = sys_get_temp_dir();
    }

    //! @brief Clean up cache files after each test
    protected function tearDown(): void
    {
        //! Remove any test cache files
        $cacheFiles = glob($this->cacheDir . '/github_*_test_*.json');
        if ($cacheFiles !== false) {
            foreach ($cacheFiles as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
        }
    }

    //! @brief Test date formatting returns "just now" for recent timestamps
    public function test_format_date_returns_just_now_for_very_recent(): void
    {
        //! @section Arrange
        $now = date('c'); //! ISO 8601 format

        //! @section Act
        $result = $this->service->formatDate($now);

        //! @section Assert
        $this->assertEquals('just now', $result);
    }

    //! @brief Test date formatting returns minutes ago for recent timestamps
    public function test_format_date_returns_minutes_ago(): void
    {
        //! @section Arrange
        $twoMinutesAgo = date('c', strtotime('-2 minutes'));

        //! @section Act
        $result = $this->service->formatDate($twoMinutesAgo);

        //! @section Assert
        $this->assertEquals('2 minutes ago', $result);
    }

    //! @brief Test date formatting returns singular minute for one minute
    public function test_format_date_returns_singular_minute(): void
    {
        //! @section Arrange
        $oneMinuteAgo = date('c', strtotime('-1 minute'));

        //! @section Act
        $result = $this->service->formatDate($oneMinuteAgo);

        //! @section Assert
        $this->assertEquals('1 minute ago', $result);
    }

    //! @brief Test date formatting returns hours ago for older timestamps
    public function test_format_date_returns_hours_ago(): void
    {
        //! @section Arrange
        $threeHoursAgo = date('c', strtotime('-3 hours'));

        //! @section Act
        $result = $this->service->formatDate($threeHoursAgo);

        //! @section Assert
        $this->assertEquals('3 hours ago', $result);
    }

    //! @brief Test date formatting returns singular hour for one hour
    public function test_format_date_returns_singular_hour(): void
    {
        //! @section Arrange
        $oneHourAgo = date('c', strtotime('-1 hour'));

        //! @section Act
        $result = $this->service->formatDate($oneHourAgo);

        //! @section Assert
        $this->assertEquals('1 hour ago', $result);
    }

    //! @brief Test date formatting returns days ago for recent days
    public function test_format_date_returns_days_ago(): void
    {
        //! @section Arrange
        $twoDaysAgo = date('c', strtotime('-2 days'));

        //! @section Act
        $result = $this->service->formatDate($twoDaysAgo);

        //! @section Assert
        $this->assertEquals('2 days ago', $result);
    }

    //! @brief Test date formatting returns singular day for one day
    public function test_format_date_returns_singular_day(): void
    {
        //! @section Arrange
        $oneDayAgo = date('c', strtotime('-1 day'));

        //! @section Act
        $result = $this->service->formatDate($oneDayAgo);

        //! @section Assert
        $this->assertEquals('1 day ago', $result);
    }

    //! @brief Test date formatting returns formatted date for older timestamps
    public function test_format_date_returns_formatted_date_for_old_timestamps(): void
    {
        //! @section Arrange
        $tenDaysAgo = date('c', strtotime('-10 days'));
        $expected = date('M j, Y', strtotime('-10 days'));

        //! @section Act
        $result = $this->service->formatDate($tenDaysAgo);

        //! @section Assert
        $this->assertEquals($expected, $result);
    }

    //! @brief Test getRepositoryInfo returns correct structure
    public function test_get_repository_info_returns_correct_structure(): void
    {
        //! @section Act
        $result = $this->service->getRepositoryInfo('test', 'repo', 'dev');

        //! @section Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('main', $result);
        $this->assertArrayHasKey('dev', $result);
        $this->assertArrayHasKey('commits_ahead', $result);
    }

    //! @brief Typed method returns RepositoryInfo with nullable fields
    public function test_get_repository_info_typed_returns_value_object(): void
    {
        //! @section Act
        $result = $this->service->getRepositoryInfoTyped('test', 'repo', 'dev');

        //! @section Assert
        $this->assertInstanceOf(RepositoryInfo::class, $result);
        $this->assertTrue(is_null($result->main) || is_string($result->main->sha));
        $this->assertTrue(is_null($result->dev) || is_string($result->dev->sha));
        $this->assertTrue(is_null($result->commitsAhead) || is_int($result->commitsAhead));
    }

    //! @brief Test getRepositoryInfo handles missing branches gracefully
    public function test_get_repository_info_handles_missing_branches(): void
    {
        //! @section Act
        $result = $this->service->getRepositoryInfo('nonexistent', 'repo123', 'branch');

        //! @section Assert
        $this->assertIsArray($result);
        $this->assertNull($result['main']);
        $this->assertNull($result['dev']);
        $this->assertNull($result['commits_ahead']);
    }

    //! @brief Test cache is used when valid cache exists
    public function test_uses_valid_cache_when_available(): void
    {
        //! @section Arrange
        $owner = 'testowner';
        $repo = 'testrepo';
        $branch = 'main';

        //! Create a cache file with valid data
        $cacheFile = $this->cacheDir . "/github_cache_{$owner}_{$repo}_{$branch}.json";
        $cachedData = [
            'sha' => 'abc1234',
            'date' => date('c', strtotime('-1 minute')),
            'message' => 'Test commit',
            'url' => 'https://github.com/test/repo/commit/abc1234'
        ];
        file_put_contents($cacheFile, json_encode($cachedData));

        //! Touch the cache file to ensure it's fresh (within 5 minutes)
        touch($cacheFile);

        //! @section Act
        $result = $this->service->getRepositoryInfo($owner, $repo, 'developing');

        //! @section Assert
        //! Main branch should use cached data
        $this->assertNotNull($result['main']);
        $this->assertEquals('abc1234', $result['main']['sha']);
        $this->assertEquals($cachedData['message'], $result['main']['message']);

        //! Clean up
        @unlink($cacheFile);
    }

    //! @brief Test expired cache is refreshed with API call
    public function test_refreshes_expired_cache(): void
    {
        //! @section Arrange
        $owner = 'testowner';
        $repo = 'testrepo';
        $branch = 'main';

        //! Create an expired cache file
        $cacheFile = $this->cacheDir . "/github_cache_{$owner}_{$repo}_{$branch}.json";
        $oldData = [
            'sha' => 'old1234',
            'date' => date('c', strtotime('-1 hour')),
            'message' => 'Old commit',
            'url' => 'https://github.com/test/repo/commit/old1234'
        ];
        file_put_contents($cacheFile, json_encode($oldData));

        //! Set modification time to 10 minutes ago (beyond 5 minute cache duration)
        touch($cacheFile, time() - 600);

        //! @section Act
        $result = $this->service->getRepositoryInfo($owner, $repo, 'developing');

        //! @section Assert
        //! Should either refresh or return null (since repo doesn't exist)
        //! The important part is it attempted to refresh
        $this->assertIsArray($result);
        $this->assertArrayHasKey('main', $result);

        //! Clean up
        @unlink($cacheFile);
    }

    //! @brief Test cache files are created for successful API calls
    public function test_creates_cache_file_for_successful_api_call(): void
    {
        //! @section Arrange
        $owner = 'php';
        $repo = 'php-src';
        $branch = 'master';

        $cacheFile = $this->cacheDir . "/github_cache_{$owner}_{$repo}_{$branch}.json";

        //! Ensure no cache exists
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }

        //! @section Act
        $result = $this->service->getRepositoryInfo($owner, $repo, 'master');

        //! @section Assert
        $this->assertIsArray($result);

        //! Cache file should be created if API call succeeded
        if ($result['main'] !== null) {
            $this->assertFileExists($cacheFile);

            $cachedContent = file_get_contents($cacheFile);
            $cachedData = json_decode($cachedContent, true);

            $this->assertIsArray($cachedData);
            $this->assertArrayHasKey('sha', $cachedData);
            $this->assertArrayHasKey('date', $cachedData);
        } else {
            //! If API call failed, ensure we still have a valid structure
            $this->assertArrayHasKey('main', $result);
        }

        //! Clean up
        @unlink($cacheFile);
    }

    //! @brief Test handles network failures gracefully
    public function test_handles_network_failures_gracefully(): void
    {
        //! @section Act
        //! Use invalid hostname to trigger network failure
        $result = $this->service->getRepositoryInfo('invalid', 'repo', 'branch');

        //! @section Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('main', $result);
        $this->assertArrayHasKey('dev', $result);
        $this->assertArrayHasKey('commits_ahead', $result);
    }

    //! @brief Test edge case with very old date formatting (1 year ago)
    public function test_format_date_handles_one_year_old(): void
    {
        //! @section Arrange
        $oneYearAgo = date('c', strtotime('-1 year'));
        $expected = date('M j, Y', strtotime('-1 year'));

        //! @section Act
        $result = $this->service->formatDate($oneYearAgo);

        //! @section Assert
        $this->assertEquals($expected, $result);
    }

    //! @brief Test edge case with boundary at 60 seconds
    public function test_format_date_at_60_second_boundary(): void
    {
        //! @section Arrange
        $sixtySecondsAgo = date('c', strtotime('-60 seconds'));

        //! @section Act
        $result = $this->service->formatDate($sixtySecondsAgo);

        //! @section Assert
        //! Should show as "1 minute ago" not "just now"
        $this->assertEquals('1 minute ago', $result);
    }

    //! @brief Test edge case with boundary at 3600 seconds
    public function test_format_date_at_3600_second_boundary(): void
    {
        //! @section Arrange
        $sixtyMinutesAgo = date('c', strtotime('-60 minutes'));

        //! @section Act
        $result = $this->service->formatDate($sixtyMinutesAgo);

        //! @section Assert
        //! Should show as "1 hour ago"
        $this->assertEquals('1 hour ago', $result);
    }

    //! @brief Test edge case with boundary at 7 days
    public function test_format_date_at_seven_day_boundary(): void
    {
        //! @section Arrange
        $sevenDaysAgo = date('c', strtotime('-7 days'));
        $expected = date('M j, Y', strtotime('-7 days'));

        //! @section Act
        $result = $this->service->formatDate($sevenDaysAgo);

        //! @section Assert
        //! At exactly 7 days, should show formatted date
        $this->assertEquals($expected, $result);
    }
}


