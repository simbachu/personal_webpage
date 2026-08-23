#!/usr/bin/env php
<?php

declare(strict_types=1);

//! @brief Render the CV to PDF with WeasyPrint so Inter digits stay copyable
//!
//! Chrome's print-to-PDF maps Inter tabular/calt figures (especially 1) onto
//! private-use glyphs. WeasyPrint embeds Unicode from the HTML source instead.

require_once __DIR__ . '/../vendor/autoload.php';

use App\Cv\CvLoader;
use App\Cv\CvPrintHtml;
use App\Shared\Support\FilePath;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

function main(): int
{
    $projectRoot = dirname(__DIR__);
    $options = getopt('', ['source:', 'lang:', 'out:', 'weasyprint:']);

    $source = is_string($options['source'] ?? null) ? $options['source'] : 'content/cv/cv.json';
    $lang = is_string($options['lang'] ?? null) ? $options['lang'] : 'en';
    $out = is_string($options['out'] ?? null) ? $options['out'] : 'build/cv.pdf';
    $weasyprint = is_string($options['weasyprint'] ?? null)
        ? $options['weasyprint']
        : (getenv('WEASYPRINT') ?: 'weasyprint');

    $sourcePath = $projectRoot . '/' . $source;
    if (!is_file($sourcePath)) {
        $sourcePath = $source;
    }

    $loader = new FilesystemLoader();
    $loader->addPath($projectRoot . '/src/Cv/templates', 'cv');
    $twig = new Environment($loader, [
        'autoescape' => 'html',
        'strict_variables' => true,
        'cache' => false,
    ]);

    $cv = CvLoader::fromString($sourcePath)->load($lang)->toArray();
    $html = $twig->render('@cv/cv.twig', ['cv' => $cv, 'print' => true]);
    $html = (new CvPrintHtml(
        FilePath::fromString($projectRoot . '/public/fonts/inter.css')
    ))->prepare($html);

    $outPath = FilePath::fromString(
        preg_match('/^[A-Za-z]:[\\\\\\/]/', $out) === 1 || str_starts_with($out, '/')
            ? $out
            : $projectRoot . '/' . $out
    );
    $outPath->getDirectory()->ensureDirectoryExists();

    $htmlPath = FilePath::fromString(
        $outPath->getDirectory()->getValue() . '/' . $outPath->getFilenameWithoutExtension() . '.print.html'
    );
    $htmlPath->writeContents($html);

    $process = proc_open(
        [$weasyprint, $htmlPath->getValue(), $outPath->getValue()],
        [],
        $pipes
    );

    if (!is_resource($process)) {
        fwrite(STDERR, "Failed to start WeasyPrint.\n");
        return 1;
    }

    $status = proc_close($process);
    if ($status !== 0) {
        fwrite(STDERR, "WeasyPrint failed with exit code {$status}.\n");
        return $status;
    }

    fwrite(STDOUT, $outPath->getValue() . "\n");
    return 0;
}

exit(main());
