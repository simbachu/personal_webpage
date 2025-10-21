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

// Use the original working templates path
define('TEMPLATES_DIR', $base_path . '/httpd.private' . $env_prefix . '/templates');

// Load Composer autoloader
require_once $vendor_autoload;

// Import classes for type safety
use App\Type\RepositoryIdentifier;
use App\Type\BranchName;
use App\Type\TemplateName;
use App\Type\FilePath;
use App\Type\Route;
use App\Router\Router;
use App\Router\Handler\HomeRouteHandler;
use App\Router\Handler\DexRouteHandler;
use App\Router\Handler\ArticleRouteHandler;

// Try different possible locations for content (this is where the issue was)
$possible_content_paths = [
    $base_path . $env_prefix . '/content',  // Local dev structure
    $base_path . '/httpd.private' . $env_prefix . '/content',  // Production structure
];

$content_path = null;
foreach ($possible_content_paths as $path) {
    if (is_dir($path)) {
        $content_path = $path;
        break;
    }
}

if ($content_path === null) {
    die('Error: Could not find content directory in any expected location');
}
$contentRepository = new \App\Model\ContentRepository(FilePath::fromString($content_path));
$homePresenter = new \App\Presenter\HomePresenter($contentRepository);

// Configure cache TTL based on environment
$pokeApiCacheTtl = $is_dev ? 30 : 300; // 30 seconds for dev, 5 minutes for production
$pokeApiService = new \App\Service\PokeApiService();
$opinionsFilePath = $content_path . '/pokemon_opinions.yaml';
$opinionService = new \App\Service\PokemonOpinionService($opinionsFilePath);
$dexPresenter = new \App\Presenter\DexPresenter($pokeApiService, $opinionService, $pokeApiCacheTtl);


// Initialize Twig
$loader = new \Twig\Loader\FilesystemLoader(TEMPLATES_DIR);
$twigOptions = [
    'autoescape' => 'html',
    'strict_variables' => true,
];

if ($is_dev) {
    $twigOptions['cache'] = false; // Disable cache in development
    $twigOptions['debug'] = true;
} else {
    $twigOptions['cache'] = sys_get_temp_dir() . '/twig'; // Enable cache in production
    $twigOptions['debug'] = false;
}

$twig = new \Twig\Environment($loader, $twigOptions);

//! @brief Renders a Twig template
//! @param TemplateName $template Template name (without .twig extension)
//! @param array $data Data to pass to template
function render(TemplateName $template, array $data = []): void {
    global $twig;
    // Validate template exists to fail-fast with a clear error
    $template->ensureExists(FilePath::fromString(TEMPLATES_DIR));
    echo $twig->render($template->toTwigPath(), $data);
}

//! @brief Gets the current request path
//! @return string Normalized path (e.g., '/', '/about')
function get_request_path(): string {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    // Normalize: remove trailing slash unless it's root
    if ($path !== '/' && str_ends_with($path, '/')) {
        $path = rtrim($path, '/');
    }
    return $path;
}

//! @brief Builds base URL for the site
//! @return string Base URL (e.g., 'https://example.com')
function get_base_url(): string {
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'];
}

// Initialize router and configure routes
$router = new Router();

// Add routes to the router
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

// Add article routes
$router->addRoute(new Route(
    '/read',
    TemplateName::ARTICLE,
    [],
    ['handler' => 'article']
));

$router->addRoute(new Route(
    '/article',
    TemplateName::ARTICLE,
    [],
    ['handler' => 'article']
));

$router->addRoute(new Route(
    '/blog',
    TemplateName::ARTICLE,
    [],
    ['handler' => 'article']
));

// Register route handlers
$router->registerHandler('home', new HomeRouteHandler($homePresenter));
$router->registerHandler('dex', new DexRouteHandler($dexPresenter));
$router->registerHandler('article', new ArticleRouteHandler($content_path));

// Get current request
$path = get_request_path();
$base_url = get_base_url();
$current_url = $base_url . $_SERVER['REQUEST_URI'];

// Route the request
$routeResult = $router->route($path);

// Set HTTP status code
http_response_code($routeResult->getStatusCode()->getValue());

// Fetch GitHub info for footer
$githubService = new \App\Service\GitHubService();
$github_info = $githubService->getRepositoryInfoTyped(RepositoryIdentifier::fromString('simbachu/personal_webpage'));

// Format GitHub dates for Twig
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

// Prepare common template data
$commonData = [
    'github' => $github,
    'base_url' => $base_url,
    'current_url' => $current_url,
    'canonical_url' => $current_url,
    'cache_bust' => time(),
    'current_year' => date('Y'),
];

// Merge route result data with common data
$templateData = array_merge($commonData, $routeResult->getData());

// Render the template
render($routeResult->getTemplate(), $templateData);