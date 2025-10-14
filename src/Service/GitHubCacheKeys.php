<?php

declare(strict_types=1);

namespace App\Service;

use App\Type\BranchName;
use App\Type\FilePath;
use App\Type\RepositoryIdentifier;

//! @brief Centralized cache key builder for GitHubService
final class GitHubCacheKeys
{
    private static function sanitize(string $value): string
    {
        // Keep to safe subset for filenames
        return preg_replace('/[^A-Za-z0-9_.-]/', '_', $value) ?? '_';
    }

    public static function branch(FilePath $dir, RepositoryIdentifier $repoId, BranchName $branch): FilePath
    {
        $owner = self::sanitize($repoId->getOwner());
        $repo = self::sanitize($repoId->getRepository());
        $br = self::sanitize($branch->getValue());
        return $dir->join("github_cache_{$owner}_{$repo}_{$br}.json");
    }

    public static function compare(FilePath $dir, RepositoryIdentifier $repoId, BranchName $base, BranchName $head): FilePath
    {
        $owner = self::sanitize($repoId->getOwner());
        $repo = self::sanitize($repoId->getRepository());
        $b = self::sanitize($base->getValue());
        $h = self::sanitize($head->getValue());
        return $dir->join("github_compare_{$owner}_{$repo}_{$b}_{$h}.json");
    }
}


