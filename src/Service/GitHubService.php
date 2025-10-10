<?php

declare(strict_types=1);

namespace App\Service;

//! @brief Service for fetching GitHub repository information with caching
//!
//! Handles API calls to GitHub with smart cache fallback for resilience.
class GitHubService
{
    private const CACHE_DURATION = 300; //!< Cache duration in seconds (5 minutes)

    //! @brief Handles API call failures with smart cache fallback
    //! @param cache_file Path to cache file
    //! @retval array|null Cached data if appropriate, null if branch doesn't exist
    private function handleApiFailure(string $cache_file): ?array
    {
        // Check if this was a 404 (branch doesn't exist) vs network error
        if (isset($GLOBALS['http_response_header'])) {
            foreach ($GLOBALS['http_response_header'] as $header) {
                if (strpos($header, '404') !== false) {
                    // Branch doesn't exist - delete stale cache and return null
                    if (file_exists($cache_file)) {
                        @unlink($cache_file);
                    }
                    return null;
                }
            }
        }

        // Network error or other issue - fall back to cache
        if (file_exists($cache_file)) {
            $cached_data = file_get_contents($cache_file);
            return json_decode($cached_data, true);
        }
        return null;
    }

    //! @brief Makes a cached GitHub API request
    //! @param url GitHub API URL
    //! @param cache_file Path to cache file
    //! @retval array|null Decoded JSON response or null on failure
    private function fetchCachedApi(string $url, string $cache_file): ?array
    {
        // Check cache validity
        if (file_exists($cache_file) && (time() - filemtime($cache_file)) < self::CACHE_DURATION) {
            $cached_data = file_get_contents($cache_file);
            return json_decode($cached_data, true);
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
    private function fetchBranchInfo(string $owner, string $repo, string $branch): ?array
    {
        $cache_file = sys_get_temp_dir() . "/github_cache_{$owner}_{$repo}_{$branch}.json";
        $url = "https://api.github.com/repos/{$owner}/{$repo}/branches/{$branch}";

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

        file_put_contents($cache_file, json_encode($commit_info));

        return $commit_info;
    }

    //! @brief Fetches commit comparison between two branches
    //! @param owner Repository owner
    //! @param repo Repository name
    //! @param base Base branch
    //! @param head Head branch to compare
    //! @retval int|null Number of commits ahead, or null on failure
    private function fetchCommitsAhead(string $owner, string $repo, string $base, string $head): ?int
    {
        $cache_file = sys_get_temp_dir() . "/github_compare_{$owner}_{$repo}_{$base}_{$head}.json";
        $url = "https://api.github.com/repos/{$owner}/{$repo}/compare/{$base}...{$head}";

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

        file_put_contents($cache_file, json_encode($compare_info));

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
    public function getRepositoryInfo(string $owner, string $repo, string $dev_branch = 'developing'): array
    {
        $main_info = $this->fetchBranchInfo($owner, $repo, 'main');
        $dev_info = $this->fetchBranchInfo($owner, $repo, $dev_branch);
        $commits_ahead = $this->fetchCommitsAhead($owner, $repo, 'main', $dev_branch);

        return [
            'main' => $main_info,
            'dev' => $dev_info,
            'commits_ahead' => $commits_ahead
        ];
    }
}

