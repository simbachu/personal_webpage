<?php

declare(strict_types=1);

namespace App\Service;

use App\Type\TournamentFormat;
use Symfony\Component\Yaml\Yaml;
use InvalidArgumentException;

//! @brief Loads tournament configuration from a YAML file
final class TournamentFormatLoader
{
    //! @brief Load a named tournament setup from a YAML config file
    //! @param path Path to YAML file
    //! @param key Top-level key identifying the tournament setup
    //! @return TournamentFormat Parsed and validated format
    public function load(string $path, string $key): TournamentFormat
    {
        $data = Yaml::parseFile($path);
        if (!is_array($data) || !isset($data[$key]) || !is_array($data[$key])) {
            throw new InvalidArgumentException("Tournament config '$key' not found in $path");
        }

        $cfg = $data[$key];
        $format = (string)($cfg['format'] ?? 'swiss-tournament');
        $playoff = isset($cfg['playoff']) ? (string)$cfg['playoff'] : null;
        $playoffCutoff = isset($cfg['playoff-cutoff']) ? (int)$cfg['playoff-cutoff'] : null;
        $playoffReset = (bool)($cfg['playoff-reset'] ?? true);

        $this->validate($format, $playoff, $playoffCutoff, $playoffReset);

        return new TournamentFormat($format, $playoff, $playoffCutoff, $playoffReset);
    }

    private function validate(string $format, ?string $playoff, ?int $cutoff, bool $reset): void
    {
        if ($format !== 'swiss-tournament') {
            throw new InvalidArgumentException("Unsupported format '$format'");
        }
        if ($playoff !== null && !in_array($playoff, ['single-elimination', 'double-elimination'], true)) {
            throw new InvalidArgumentException("Unsupported playoff '$playoff'");
        }
        if ($playoff !== null) {
            if ($cutoff === null || $cutoff < 2) {
                throw new InvalidArgumentException('playoff-cutoff must be >= 2 when playoff is defined');
            }
            // require power of two for now
            if (($cutoff & ($cutoff - 1)) !== 0) {
                throw new InvalidArgumentException('playoff-cutoff must be a power of two');
            }
        }
        // $reset is boolean already; no extra checks needed
    }
}


