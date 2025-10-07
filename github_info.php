<?php
//! @brief Handles API call failures with smart cache fallback
//! @param string $cache_file Path to cache file
//! @return array|null Cached data if appropriate, null if branch doesn't exist
function handle_api_failure($cache_file) {
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
//! @param string $url GitHub API URL
//! @param string $cache_file Path to cache file
//! @param int $cache_duration Cache duration in seconds
//! @return array|null Decoded JSON response or null on failure
function fetch_cached_github_api($url, $cache_file, $cache_duration = 300) {
    // Check cache validity
    if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $cache_duration) {
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
        return handle_api_failure($cache_file);
    }

    return json_decode($response, true);
}

//! @brief Fetches GitHub commit information for a specific branch
//! @param string $owner Repository owner
//! @param string $repo Repository name
//! @param string $branch Branch name
//! @return array|null Array with commit info or null on failure
function fetch_github_branch_info($owner, $repo, $branch) {
    $cache_file = sys_get_temp_dir() . "/github_cache_{$owner}_{$repo}_{$branch}.json";
    $url = "https://api.github.com/repos/{$owner}/{$repo}/branches/{$branch}";

    $data = fetch_cached_github_api($url, $cache_file);

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

//! @brief Formats a GitHub commit date into a human-readable string
//! @param string $date ISO 8601 date string
//! @return string Formatted date
function format_github_date($date) {
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

//! @brief Fetches commit comparison between two branches
//! @param string $owner Repository owner
//! @param string $repo Repository name
//! @param string $base Base branch
//! @param string $head Head branch to compare
//! @return int|null Number of commits ahead, or null on failure
function fetch_commits_ahead($owner, $repo, $base, $head) {
    $cache_file = sys_get_temp_dir() . "/github_compare_{$owner}_{$repo}_{$base}_{$head}.json";
    $url = "https://api.github.com/repos/{$owner}/{$repo}/compare/{$base}...{$head}";

    $data = fetch_cached_github_api($url, $cache_file);

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

//! @brief Gets commit information for both main and development branches
//! @param string $owner Repository owner
//! @param string $repo Repository name
//! @param string $dev_branch Development branch name (defaults to 'developing')
//! @return array Array with 'main' and 'dev' branch info
function get_github_info($owner, $repo, $dev_branch = 'developing') {
    $main_info = fetch_github_branch_info($owner, $repo, 'main');
    $dev_info = fetch_github_branch_info($owner, $repo, $dev_branch);
    $commits_ahead = fetch_commits_ahead($owner, $repo, 'main', $dev_branch);

    return [
        'main' => $main_info,
        'dev' => $dev_info,
        'commits_ahead' => $commits_ahead
    ];
}
