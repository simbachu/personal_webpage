<?php
//! @brief Front Controller - handles routing and template rendering with Twig

// Define paths based on deployment structure
define('PUBLIC_DIR', __DIR__);

// Detect if we're in a dev subdirectory by checking if current directory is named 'dev'
$is_dev = (basename(__DIR__) === 'dev');

// Build paths for different deployment structures
if ($is_dev) {
    // Production dev environment: /httpd.www/dev/ -> /dev/
    $base_path = dirname(dirname(__DIR__));
    $env_prefix = '/dev';
} else {
    // Production main or local development
    $base_path = dirname(__DIR__);
    $env_prefix = '';
}

// Use the original working vendor autoload path
$vendor_autoload = $base_path . '/httpd.private' . $env_prefix . '/vendor/autoload.php';
if (!file_exists($vendor_autoload)) {
    $vendor_autoload = $base_path . '/vendor/autoload.php';
}

// Load Composer autoloader
require_once $vendor_autoload;

use App\Shared\Github\RepositoryIdentifier;
use App\Shared\Http\TemplateName;
use App\Shared\Support\FilePath;
use App\Shared\Http\Route;
use App\Shared\Http\Router;
use App\Site\Home\HomeRouteHandler;
use App\Site\Home\ContentRepository;
use App\Site\Home\HomePresenter;
use App\Dex\DexRouteHandler;
use App\Dex\PokeApiService;
use App\Dex\PokemonOpinionService;
use App\Dex\DexPresenter;
use App\Cv\CvLoader;
use App\Cv\CvLanguageSelector;
use App\Cv\CvRouteHandler;
use App\Site\Article\ArticleRouteHandler;
use App\Site\Article\FileArticleRepository;
use App\Site\Article\MarkdownProcessor;
use App\Shared\Github\GitHubService;
use App\Benefactor\BenefactorRequest;
use App\Benefactor\BenefactorRouteHandler;
use App\Benefactor\BenefactorSession;
use App\Benefactor\MemberCache;
use App\Benefactor\MemberMarkup;
use App\Benefactor\OAuth\PatreonOAuthClient;
use App\Benefactor\PatreonHttp;
use App\Benefactor\PatreonMemberService;

//! @brief Resolve private app root (contains src/, content/, vendor/)
//! @return string Absolute path to private root
function resolve_private_root(string $base_path, string $env_prefix): string
{
    $candidates = [
        $base_path . '/httpd.private' . $env_prefix,
        $base_path . $env_prefix,
        $base_path,
    ];

    foreach ($candidates as $candidate) {
        if (is_dir($candidate . '/src') && is_dir($candidate . '/content')) {
            return $candidate;
        }
    }

    die('Error: Could not find private application root with src/ and content/');
}

$private_root = resolve_private_root($base_path, $env_prefix);
$site_content_path = $private_root . '/content/site';
$dex_content_path = $private_root . '/content/dex';
$cv_content_path = $private_root . '/content/cv';

if (!is_dir($site_content_path)) {
    die('Error: Could not find site content directory at ' . $site_content_path);
}

$shared_templates = $private_root . '/src/Shared/templates';
$site_templates = $private_root . '/src/Site/templates';
$dex_templates = $private_root . '/src/Dex/templates';
$cv_templates = $private_root . '/src/Cv/templates';
$benefactor_templates = $private_root . '/src/Benefactor/templates';

$template_namespace_paths = [
    'shared' => FilePath::fromString($shared_templates),
    'site' => FilePath::fromString($site_templates),
    'dex' => FilePath::fromString($dex_templates),
    'cv' => FilePath::fromString($cv_templates),
    'benefactor' => FilePath::fromString($benefactor_templates),
];

$contentRepository = new ContentRepository(FilePath::fromString($site_content_path));
$homePresenter = new HomePresenter($contentRepository);

// Configure cache TTL based on environment
$pokeApiCacheTtl = $is_dev ? 30 : 300; // 30 seconds for dev, 5 minutes for production
$pokeApiService = new PokeApiService();
$opinionsFilePath = $dex_content_path . '/pokemon_opinions.yaml';
$opinionService = new PokemonOpinionService($opinionsFilePath);
$dexPresenter = new DexPresenter($pokeApiService, $opinionService, $pokeApiCacheTtl);

$cvLoader = CvLoader::fromString($cv_content_path . '/cv.json');

// Initialize Twig with slice namespaces
$loader = new \Twig\Loader\FilesystemLoader();
$loader->addPath($shared_templates, 'shared');
$loader->addPath($site_templates, 'site');
$loader->addPath($dex_templates, 'dex');
$loader->addPath($cv_templates, 'cv');
$loader->addPath($benefactor_templates, 'benefactor');

$twigOptions = [
    'autoescape' => 'html',
    'strict_variables' => true,
];

if ($is_dev) {
    $twigOptions['cache'] = false;
    $twigOptions['debug'] = true;
} else {
    $twigOptions['cache'] = sys_get_temp_dir() . '/twig';
    $twigOptions['debug'] = false;
}

$twig = new \Twig\Environment($loader, $twigOptions);

//! @brief Renders a Twig template
//! @param TemplateName $template Template name
//! @param array $data Data to pass to template
function render(TemplateName $template, array $data = []): void
{
    global $twig, $template_namespace_paths;
    $template->ensureExists($template_namespace_paths);
    echo $twig->render($template->toTwigPath(), $data);
}

//! @brief Gets the current request path
//! @return string Normalized path (e.g., '/', '/about')
function get_request_path(): string
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    if ($path !== '/' && str_ends_with($path, '/')) {
        $path = rtrim($path, '/');
    }
    return $path;
}

//! @brief Builds base URL for the site
//! @return string Base URL (e.g., 'https://example.com')
function get_base_url(): string
{
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'];
}

// Initialize router and configure routes
$router = new Router();

$router->addRoute(new Route(
    '/',
    TemplateName::HOME,
    [
        'title' => 'Jennifer Gott',
        'description' => 'Software designer, information engineer, and illustrator based in Gothenburg, Sweden. Currently studying System Development at Chas Academy, specialized in C/C++, embedded development, and technical illustration.',
        'og_title' => 'Jennifer Gott - Software Designer & Information Engineer',
    ],
    ['handler' => 'home']
));

$router->addRoute(new Route(
    '/dex',
    TemplateName::DEX,
    [],
    ['handler' => 'dex']
));

$router->addRoute(new Route(
    '/dex/{id_or_name}',
    TemplateName::DEX,
    [],
    ['handler' => 'dex']
));

$router->addRoute(new Route(
    '/read/{article_name}',
    TemplateName::ARTICLE,
    [],
    ['handler' => 'article']
));

$router->addRoute(new Route(
    '/article/{article_name}',
    TemplateName::ARTICLE,
    [],
    ['handler' => 'article']
));

$router->addRoute(new Route(
    '/blog/{article_name}',
    TemplateName::ARTICLE,
    [],
    ['handler' => 'article']
));

$router->addRoute(new Route(
    '/cv',
    TemplateName::CV,
    [
        'title' => 'Jennifer Gott — CV',
        'description' => 'Curriculum vitae for Jennifer Jonathan Gott — systems developer based in Gothenburg, Sweden.',
    ],
    ['handler' => 'cv']
));

$router->addRoute(new Route(
    '/benefactor',
    TemplateName::BENEFACTOR,
    [
        'title' => 'Benefactor',
        'description' => 'Copy a ranked list of your Patreon members as HTML.',
    ],
    ['handler' => 'benefactor']
));

$markdownProcessor = new MarkdownProcessor();
$articleRepository = new FileArticleRepository($site_content_path, $markdownProcessor);

$router->registerHandler('home', new HomeRouteHandler($homePresenter));
$router->registerHandler('dex', new DexRouteHandler($dexPresenter));
$router->registerHandler('article', new ArticleRouteHandler($articleRepository));
$queryLanguage = isset($_GET['lang']) && is_string($_GET['lang']) ? $_GET['lang'] : null;
$acceptLanguage = isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])
    && is_string($_SERVER['HTTP_ACCEPT_LANGUAGE'])
    ? $_SERVER['HTTP_ACCEPT_LANGUAGE']
    : null;
$cvLanguage = (new CvLanguageSelector())->select($queryLanguage, $acceptLanguage);
$router->registerHandler('cv', new CvRouteHandler($cvLoader, $cvLanguage));

$path = get_request_path();
$base_url = get_base_url();
$current_url = $base_url . $_SERVER['REQUEST_URI'];

if ($path === '/benefactor' && session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if ($path === '/benefactor') {
    $benefactorSession = new BenefactorSession($_SESSION);
} else {
    $benefactorSessionStorage = [];
    $benefactorSession = new BenefactorSession($benefactorSessionStorage);
}

$patreonHttp = PatreonHttp::createClient();
$router->registerHandler('benefactor', new BenefactorRouteHandler(
    new PatreonOAuthClient(
        (string) (getenv('PATREON_CLIENT_ID') ?: ''),
        (string) (getenv('PATREON_CLIENT_SECRET') ?: ''),
        $patreonHttp
    ),
    new MemberCache(
        new PatreonMemberService($patreonHttp),
        FilePath::fromString(sys_get_temp_dir() . '/benefactor_cache')
    ),
    new MemberMarkup(),
    $benefactorSession,
    $base_url . $env_prefix . '/benefactor',
    new BenefactorRequest(
        isset($_GET['code']) && is_string($_GET['code']) ? $_GET['code'] : null,
        isset($_GET['state']) && is_string($_GET['state']) ? $_GET['state'] : null,
        isset($_GET['error']) && is_string($_GET['error']) ? $_GET['error'] : null
    )
));

if ($path === '/cv') {
    header('Vary: Accept-Language');
}

$routeResult = $router->route($path);

if ($routeResult->getRedirectUrl() !== null) {
    http_response_code($routeResult->getStatusCode()->getValue());
    header('Location: ' . str_replace(["\r", "\n"], '', $routeResult->getRedirectUrl()));
    exit;
}

http_response_code($routeResult->getStatusCode()->getValue());

$githubService = new GitHubService();
$github_info = $githubService->getRepositoryInfoTyped(RepositoryIdentifier::fromString('simbachu/personal_webpage'));

$github = [
    'main' => $github_info->main ? [
        'url' => $github_info->main->url,
        'message' => $github_info->main->message,
        'date_formatted' => $githubService->formatDate($github_info->main->date),
    ] : null,
    'dev' => $github_info->dev ? [
        'url' => $github_info->dev->url,
        'message' => $github_info->dev->message,
        'date_formatted' => $githubService->formatDate($github_info->dev->date),
    ] : null,
    'commits_ahead' => $github_info->commitsAhead ?? 0,
];

$commonData = [
    'github' => $github,
    'base_url' => $base_url,
    'current_url' => $current_url,
    'canonical_url' => $current_url,
    'cache_bust' => time(),
    'current_year' => date('Y'),
];

$templateData = array_merge($commonData, $routeResult->getData());

render($routeResult->getTemplate(), $templateData);
