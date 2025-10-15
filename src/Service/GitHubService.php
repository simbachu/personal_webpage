<?php

declare(strict_types=1);

namespace App\Service;

use App\Type\FilePath;
use App\Type\CommitInfo;
use App\Type\RepositoryInfo;
use App\Type\RepositoryIdentifier;
use App\Type\BranchName;

//! @brief Service for fetching GitHub repository information with caching
//!
//! Handles API calls to GitHub with smart cache fallback for resilience.
class GitHubService
{
    private const CACHE_DURATION = 300; //!< Cache duration in seconds (5 minutes)

    //! @brief Handles API call failures with smart cache fallback
    //! @param cache_file Path to cache file
    //! @retval array|null Cached data if appropriate, null if branch doesn't exist
    private function handleApiFailure(FilePath $cache_file): ?array
    {
        // Check if this was a 404 (branch doesn't exist) vs network error
        if (isset($GLOBALS['http_response_header'])) {
            foreach ($GLOBALS['http_response_header'] as $header) {
                if (strpos($header, '404') !== false) {
                    // Branch doesn't exist - delete stale cache and return null
                    if ($cache_file->exists()) {
                        $cache_file->delete();
                    }
                    return null;
                }
            }
        }

        // Network error or other issue - fall back to cache
        if ($cache_file->exists()) {
            try {
                $cached_data = $cache_file->readContents();
                return json_decode($cached_data, true);
            } catch (\RuntimeException $e) {
                // Cache file exists but can't be read
                return null;
            }
        }
        return null;
    }

    //! @brief Makes a cached GitHub API request
    //! @param url GitHub API URL
    //! @param cache_file Path to cache file
    //! @retval array|null Decoded JSON response or null on failure
    private function fetchCachedApi(string $url, FilePath $cache_file): ?array
    {
        // Check cache validity
        if ($cache_file->exists() && !$cache_file->isOlderThan(self::CACHE_DURATION)) {
            try {
                $cached_data = $cache_file->readContents();
                return json_decode($cached_data, true);
            } catch (\RuntimeException $e) {
                // Cache file exists but can't be read, continue to API call
            }
        }

        // Create HTTP request options
        $options = [
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: PHP',
                    'Accept: application/vnd.github.v3+json'
                ],
                'timeout' => 5
            ]
        ];

        $context = stream_context_create($options);
        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            return $this->handleApiFailure($cache_file);
        }

        return json_decode($response, true);
    }

    //! @brief Fetches GitHub commit information for a specific branch
    //! @param owner Repository owner
    //! @param repo Repository name
    //! @param branch Branch name
    //! @retval array|null Array with commit info or null on failure
    private function fetchBranchInfo(RepositoryIdentifier $repoId, BranchName $branch): ?array
    {
        $cache_dir = FilePath::fromString(sys_get_temp_dir());
        $cache_file = GitHubCacheKeys::branch($cache_dir, $repoId, $branch);
        $owner = $repoId->getOwner();
        $repo = $repoId->getRepository();
        $url = "https://api.github.com/repos/{$owner}/{$repo}/branches/{$branch->getValue()}";

        $data = $this->fetchCachedApi($url, $cache_file);

        if ($data === null) {
            return null;
        }

        // Check if this is cached simplified data or fresh API data
        if (isset($data['sha']) && isset($data['date'])) {
            // Already simplified from cache
            return $data;
        }

        // Fresh API data - extract and cache
        if (!isset($data['commit'])) {
            return null;
        }

        $commit_info = [
            'sha' => substr($data['commit']['sha'], 0, 7),
            'date' => $data['commit']['commit']['committer']['date'],
            'message' => $data['commit']['commit']['message'],
            'url' => $data['commit']['html_url']
        ];

            $cache_file->writeContents(json_encode($commit_info));

        return $commit_info;
    }

    //! @brief Fetches commit comparison between two branches
    //! @param owner Repository owner
    //! @param repo Repository name
    //! @param base Base branch
    //! @param head Head branch to compare
    //! @retval int|null Number of commits ahead, or null on failure
    private function fetchCommitsAhead(RepositoryIdentifier $repoId, BranchName $base, BranchName $head): ?int
    {
        $cache_dir = FilePath::fromString(sys_get_temp_dir());
        $cache_file = GitHubCacheKeys::compare($cache_dir, $repoId, $base, $head);
        $owner = $repoId->getOwner();
        $repo = $repoId->getRepository();
        $url = "https://api.github.com/repos/{$owner}/{$repo}/compare/{$base->getValue()}...{$head->getValue()}";

        $data = $this->fetchCachedApi($url, $cache_file);

        if ($data === null) {
            return null;
        }

        // Check if this is already simplified cached data
        if (isset($data['ahead_by']) && !isset($data['status'])) {
            // Already simplified from cache
            return $data['ahead_by'];
        }

        // Fresh API data - extract and cache
        if (!isset($data['ahead_by'])) {
            return null;
        }

        $compare_info = [
            'ahead_by' => $data['ahead_by']
        ];

        $cache_file->writeContents(json_encode($compare_info));

        return $compare_info['ahead_by'];
    }

    //! @brief Formats a GitHub commit date into a human-readable string
    //! @param date ISO 8601 date string
    //! @retval string Formatted date
    public function formatDate(string $date): string
    {
        $timestamp = strtotime($date);
        $now = time();
        $diff = $now - $timestamp;

        if ($diff < 60) {
            return 'just now';
        } elseif ($diff < 3600) {
            $mins = floor($diff / 60);
            return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        } else {
            return date('M j, Y', $timestamp);
        }
    }

    //! @brief Gets commit information for both main and development branches
    //! @param owner Repository owner
    //! @param repo Repository name
    //! @param dev_branch Development branch name (defaults to 'developing')
    //! @retval array{
    //!     main: array{sha: string, date: string, message: string, url: string}|null,
    //!     dev: array{sha: string, date: string, message: string, url: string}|null,
    //!     commits_ahead: int|null
    //! } Array with 'main' and 'dev' branch info
    public function getRepositoryInfo(RepositoryIdentifier $repoId, ?BranchName $dev_branch = null): array
    {
        $dev_branch = $dev_branch ?? BranchName::fromString('developing');
        $main_info = $this->fetchBranchInfo($repoId, BranchName::fromString('main'));
        $dev_info = $this->fetchBranchInfo($repoId, $dev_branch);
        $commits_ahead = $this->fetchCommitsAhead($repoId, BranchName::fromString('main'), $dev_branch);

        return [
            'main' => $main_info,
            'dev' => $dev_info,
            'commits_ahead' => $commits_ahead
        ];
    }

    //! @brief Typed variant returning RepositoryInfo value object for clarity and safety
    //! @param owner Repository owner
    //! @param repo Repository name
    //! @param dev_branch Development branch name (defaults to 'developing')
    //! @return RepositoryInfo Repository info with optional commit summaries
    public function getRepositoryInfoTyped(RepositoryIdentifier $repoId, ?BranchName $dev_branch = null): RepositoryInfo
    {
        $dev_branch = $dev_branch ?? BranchName::fromString('developing');
        $main_raw = $this->fetchBranchInfo($repoId, BranchName::fromString('main'));
        $dev_raw = $this->fetchBranchInfo($repoId, $dev_branch);
        $ahead = $this->fetchCommitsAhead($repoId, BranchName::fromString('main'), $dev_branch);

        $main = $main_raw ? new CommitInfo(
            sha: (string)($main_raw['sha'] ?? ''),
            date: (string)($main_raw['date'] ?? ''),
            message: (string)($main_raw['message'] ?? ''),
            url: (string)($main_raw['url'] ?? '')
        ) : null;

        $dev = $dev_raw ? new CommitInfo(
            sha: (string)($dev_raw['sha'] ?? ''),
            date: (string)($dev_raw['date'] ?? ''),
            message: (string)($dev_raw['message'] ?? ''),
            url: (string)($dev_raw['url'] ?? '')
        ) : null;

        return new RepositoryInfo(
            main: $main,
            dev: $dev,
            commitsAhead: $ahead
        );
    }
}

