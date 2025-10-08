<?php
/**
 * Front Controller - handles routing and template rendering with Twig
 */

// Define paths based on deployment structure
// Main deployment: /www/index.php         → private=/private, vendor=/vendor
// Dev deployment:  /www/dev/index.php     → private=/private/dev, vendor=/vendor/dev
define('PUBLIC_DIR', __DIR__);

// Detect if we're in a dev subdirectory by checking path depth
$is_dev = (basename(dirname(__DIR__)) === 'www');

if ($is_dev) {
    // Dev environment: /www/dev/
    define('PRIVATE_DIR', dirname(dirname(__DIR__)) . '/private/dev');
    $vendor_autoload = dirname(dirname(__DIR__)) . '/vendor/dev/autoload.php';
} else {
    // Main environment: /www/
    define('PRIVATE_DIR', dirname(__DIR__) . '/private');
    $vendor_autoload = dirname(__DIR__) . '/vendor/autoload.php';
}

// Load Composer autoloader
require_once $vendor_autoload;
require_once PRIVATE_DIR . '/github_info.php';

// Initialize Twig
$loader = new \Twig\Loader\FilesystemLoader(PRIVATE_DIR . '/templates');
$twig = new \Twig\Environment($loader, [
    'cache' => false, // Disable cache for development; enable in production
    'autoescape' => 'html',
    'strict_variables' => true,
]);

/**
 * Renders a Twig template
 * @param string $template Template name (without .twig extension)
 * @param array $data Data to pass to template
 */
function render(string $template, array $data = []): void {
    global $twig;
    echo $twig->render($template . '.twig', $data);
}

/**
 * Gets the current request path
 * @return string Normalized path (e.g., '/', '/about')
 */
function get_request_path(): string {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    // Normalize: remove trailing slash unless it's root
    if ($path !== '/' && str_ends_with($path, '/')) {
        $path = rtrim($path, '/');
    }
    return $path;
}

/**
 * Builds base URL for the site
 * @return string Base URL (e.g., 'https://example.com')
 */
function get_base_url(): string {
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'];
}

/**
 * Route definitions
 * Each route maps a path to a template and metadata
 */
$routes = [
    '/' => [
        'template' => 'home',
        'meta' => [
            'title' => 'Jennifer Gott',
            'description' => 'Software designer, information engineer, and illustrator based in Gothenburg, Sweden. Currently studying System Development at Chas Academy, specialized in C/C++, embedded development, and technical illustration.',
            'og_title' => 'Jennifer Gott - Software Designer & Information Engineer',
        ]
    ],
    // Add more routes here as needed
    // '/about' => ['template' => 'about', 'meta' => [...]],
];

// Get current request
$path = get_request_path();
$base_url = get_base_url();
$current_url = $base_url . $_SERVER['REQUEST_URI'];

// Find matching route
if (isset($routes[$path])) {
    $route = $routes[$path];
    $template = $route['template'];
    $meta = $route['meta'] ?? [];
} else {
    // 404 Not Found
    http_response_code(404);
    $template = '404';
    $meta = [
        'title' => 'Page Not Found - Jennifer Gott',
        'description' => 'The page you are looking for does not exist.',
    ];
}

// Fetch GitHub info for footer
$github_raw = get_github_info('simbachu', 'personal_webpage');

// Format GitHub dates for Twig
$github = [
    'main' => $github_raw['main'] ? [
        'url' => $github_raw['main']['url'],
        'message' => $github_raw['main']['message'],
        'date_formatted' => format_github_date($github_raw['main']['date']),
    ] : null,
    'dev' => $github_raw['dev'] ? [
        'url' => $github_raw['dev']['url'],
        'message' => $github_raw['dev']['message'],
        'date_formatted' => format_github_date($github_raw['dev']['date']),
    ] : null,
    'commits_ahead' => $github_raw['commits_ahead'] ?? 0,
];

// Render the template
render($template, [
    'meta' => $meta,
    'github' => $github,
    'base_url' => $base_url,
    'current_url' => $current_url,
    'canonical_url' => $current_url,
    'cache_bust' => time(),
    'current_year' => date('Y'),
]);
