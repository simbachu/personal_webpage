<?php

declare(strict_types=1);

namespace App\Router\Handler;

use App\Model\Article;
use App\Repository\ArticleRepository;
use App\Router\RouteHandler;
use App\Router\RouteResult;
use App\Type\Route;
use App\Type\TemplateName;
use App\Type\HttpStatusCode;

//! @brief Route handler for article/blog routes
//!
//! This handler processes article routes by using the ArticleRepository to load articles
//! and generate the appropriate response data for the template.
class ArticleRouteHandler implements RouteHandler
{
    //! @brief Construct the article route handler
    //! @param articleRepository Repository for article data access
    public function __construct(
        private readonly ArticleRepository $articleRepository
    ) {}

    //! @brief Handle article route requests
    //! @param route The matched route
    //! @param parameters Extracted route parameters
    //! @retval RouteResult The result containing template and data
    public function handle(Route $route, array $parameters = []): RouteResult
    {
        $articleName = $parameters['article_name'] ?? '';

        if (empty($articleName)) {
            return new RouteResult(
                template: TemplateName::NOT_FOUND,
                statusCode: HttpStatusCode::NOT_FOUND,
                data: [
                    'meta' => [
                        'title' => 'Article Not Found',
                        'description' => 'Article not found.',
                        'og_title' => 'Article Not Found',
                        'og_description' => 'The requested article could not be found.'
                    ]
                ]
            );
        }

        // Sanitize the article name to prevent directory traversal
        $articleName = preg_replace('/[^a-zA-Z0-9\-_]/', '', $articleName);

        if (empty($articleName)) {
            return new RouteResult(
                template: TemplateName::NOT_FOUND,
                statusCode: HttpStatusCode::NOT_FOUND,
                data: [
                    'meta' => [
                        'title' => 'Article Not Found',
                        'description' => 'Invalid article name.',
                        'og_title' => 'Article Not Found',
                        'og_description' => 'The requested article name is invalid.'
                    ]
                ]
            );
        }

        $article = $this->articleRepository->findBySlug($articleName);

        if (!$article) {
            return new RouteResult(
                template: TemplateName::NOT_FOUND,
                statusCode: HttpStatusCode::NOT_FOUND,
                data: [
                    'meta' => [
                        'title' => 'Article Not Found',
                        'description' => "Article '{$articleName}' not found in articles configuration.",
                        'og_title' => 'Article Not Found',
                        'og_description' => "The requested article '{$articleName}' could not be found."
                    ]
                ]
            );
        }

        if (!$article->isPublished()) {
            return new RouteResult(
                template: TemplateName::NOT_FOUND,
                statusCode: HttpStatusCode::NOT_FOUND,
                data: [
                    'meta' => [
                        'title' => 'Article Not Found',
                        'description' => "Article '{$articleName}' is not published.",
                        'og_title' => 'Article Not Found',
                        'og_description' => "The requested article '{$articleName}' is not available."
                    ]
                ]
            );
        }

        // Generate meta data for the article
        $meta = $this->generateMetaData($article);

        return new RouteResult(
            template: TemplateName::ARTICLE,
            statusCode: HttpStatusCode::OK,
            data: [
                'article' => [
                    'title' => $article->title,
                    'author' => $article->author,
                    'date' => $article->date,
                    'content' => $article->content,
                    'tags' => $article->tags,
                    'description' => $article->description,
                    'published' => $article->published,
                    'footnotes' => $article->footnotes
                ],
                'meta' => $meta
            ]
        );
    }

    //! @brief Generate comprehensive meta data for article pages
    //! @param article The article to generate meta data for
    //! @retval array Meta data array with title, description, og_title, og_description
    private function generateMetaData(Article $article): array
    {
        $title = $article->title;
        $description = $article->getMetaDescription();

        return [
            'title' => $title,
            'description' => $description,
            'og_title' => $title,
            'og_description' => $description
        ];
    }

}