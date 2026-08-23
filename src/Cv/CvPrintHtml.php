<?php

declare(strict_types=1);

namespace App\Cv;

use App\Shared\Support\FilePath;

//! @brief Rewrites CV HTML so WeasyPrint can load local Inter files
//!
//! Browser "Save as PDF" maps Inter's tabular/calt digit glyphs (especially 1)
//! onto private-use code points. WeasyPrint embeds from source characters
//! instead, but it still needs a filesystem URI for /fonts/inter.css.
final class CvPrintHtml
{
    public function __construct(
        private readonly FilePath $fontStylesheet
    ) {}

    public function prepare(string $html): string
    {
        return str_replace(
            'href="/fonts/inter.css"',
            'href="' . $this->toFileUri() . '"',
            $html
        );
    }

    private function toFileUri(): string
    {
        $path = str_replace('\\', '/', $this->fontStylesheet->getValue());
        if (!str_starts_with($path, '/')) {
            $path = '/' . $path;
        }

        return 'file://' . $path;
    }
}
