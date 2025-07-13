<?php
/**
 *  Router script used by `scabbard:serve` to provide a custom 404 page.
 *
 * The script checks if the requested resource exists within the document root.
 * If so, it delegates to the built-in PHP server. Otherwise it serves the
 * generated `404.html` file with a 404 status code.
 */
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$path = $_SERVER['DOCUMENT_ROOT'] . $uri;

// Let the built-in server handle existing files but not directories.
if ($uri !== '/' && is_file($path)) {
    return false;
}

// Serve index.html for directory-based routes like /blog/my-post
if ($uri !== '/' && is_dir($path)) {
    $indexFile = rtrim($path, '/\\') . '/index.html';
    if (file_exists($indexFile)) {
        readfile($indexFile);
        return true;
    }
}

// PHP does not automatically serve index.html for the root path
if ($uri === '/' || $uri === '') {
  readfile($_SERVER['DOCUMENT_ROOT'] . '/index.html');
  return true;
}


// Serve custom 404
http_response_code(404);
$notFoundPath = $_SERVER['DOCUMENT_ROOT'] . '/404.html';

if (file_exists($notFoundPath)) {
    readfile($notFoundPath);
} else {
    echo "<h1>404 Not Found</h1><p>The page you are looking for does not exist.</p>";
}

