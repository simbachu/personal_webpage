<?php

declare(strict_types=1);

namespace App\Service;

use App\Type\CacheVersion;
use App\Type\FilePath;
use App\Type\MonsterIdentifier;

//! @brief Centralized builder for cache file paths
final class CacheKeys
{
    private static function hashKey(string $key): string
    {
        return md5($key);
    }

    public static function pokemon(FilePath $cacheDir, CacheVersion $version, string $idOrName): FilePath
    {
        $hash = self::hashKey($idOrName);
        return $cacheDir->join(sprintf('%s_pokemon_%s.json', $version->value, $hash));
    }

    public static function pokemonForIdentifier(FilePath $cacheDir, CacheVersion $version, MonsterIdentifier $identifier): FilePath
    {
        // Rely on value object boundary to provide normalized identifier value
        $raw = $identifier->getValue();
        return self::pokemon($cacheDir, $version, $raw);
    }

    public static function pokemonById(FilePath $cacheDir, CacheVersion $version, string $id): FilePath
    {
        return self::pokemon($cacheDir, $version, $id);
    }

    public static function pokemonByName(FilePath $cacheDir, CacheVersion $version, string $nameLower): FilePath
    {
        return self::pokemon($cacheDir, $version, $nameLower);
    }

    public static function species(FilePath $cacheDir, CacheVersion $version, string $speciesUrl): FilePath
    {
        $hash = self::hashKey($speciesUrl);
        return $cacheDir->join(sprintf('%s_evolution_%s.json', $version->value, $hash));
    }

    public static function evolutionChain(FilePath $cacheDir, CacheVersion $version, string $evolutionChainUrl): FilePath
    {
        $hash = self::hashKey($evolutionChainUrl);
        return $cacheDir->join(sprintf('%s_evolution_chain_%s.json', $version->value, $hash));
    }
}


