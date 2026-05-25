<?php
/**
 * PHP built-in server router
 * Spuštění: php -S localhost:8765 router.php
 */

$uri  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// API požadavky: /api/* → api/index.php
if (preg_match('#^/api(/|$)#', $uri)) {
    // Nastavíme SCRIPT_NAME tak, aby Request::_parsePath() správně oříznul /api prefix
    $_SERVER['SCRIPT_NAME'] = '/api/index.php';
    require __DIR__ . '/api/index.php';
    return true;
}

// Existující soubor (CSS, JS, obrázky, …) – servíruj přímo
if ($uri !== '/' && file_exists(__DIR__ . $uri) && !is_dir(__DIR__ . $uri)) {
    return false;
}

// PHP stránky
$file = __DIR__ . $uri;
if (is_dir($file)) {
    $file = rtrim($file, '/') . '/index.php';
}
if (file_exists($file) && str_ends_with($file, '.php')) {
    require $file;
    return true;
}

// Fallback – 404
http_response_code(404);
echo '<h1>404 Not Found</h1><p>' . htmlspecialchars($uri) . '</p>';
return true;
