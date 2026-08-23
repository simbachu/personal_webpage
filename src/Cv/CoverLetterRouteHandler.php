<?php

declare(strict_types=1);

namespace App\Cv;

use App\Shared\Http\HttpStatusCode;
use App\Shared\Http\Route;
use App\Shared\Http\RouteHandler;
use App\Shared\Http\RouteResult;
use App\Shared\Http\TemplateName;

//! @brief Route handler for the cover letter page
class CoverLetterRouteHandler implements RouteHandler
{
    public function __construct(
        private readonly CvLoader $cvLoader,
        private readonly CoverLetterLoader $letterLoader,
        private readonly string $language = 'en'
    ) {}

    public function handle(Route $route, array $parameters = []): RouteResult
    {
        if (!$this->letterLoader->exists()) {
            return new RouteResult(
                TemplateName::NOT_FOUND,
                [
                    'meta' => [
                        'title' => 'Cover letter not found',
                        'description' => 'The requested cover letter could not be found.',
                    ],
                ],
                HttpStatusCode::NOT_FOUND
            );
        }

        $cv = $this->cvLoader->load($this->language)->toArray();
        $paragraphs = $this->letterLoader->load();

        return new RouteResult(
            TemplateName::COVER_LETTER,
            [
                'cv' => $cv,
                'paragraphs' => $paragraphs,
                'meta' => [
                    'title' => $cv['name'] . ' — Cover letter',
                    'description' => 'Cover letter for ' . $cv['name'],
                ],
            ]
        );
    }
}
