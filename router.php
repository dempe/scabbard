<?php

/**
 *  Router script used by `scabbard:serve` to provide a custom 404 page.
 *
 * The script checks if the requested resource exists within the document root.
 * If so, it delegates to the built-in PHP server. Otherwise it serves the
 * generated `404.html` file with a 404 status code.
 */
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$path = $_SERVER['DOCUMENT_ROOT'] . $uri;

function logRequest(string $method, string $uri, int $status): void
{
  file_put_contents('php://stderr', sprintf("%s %s %d\n", $method, $uri, $status));
}

function serveNotFound(string $method, string $uri): void
{
  http_response_code(404);
  $custom404 = getenv('SCABBARD_NOT_FOUND') ?: '/404.html';
  $notFoundPath = $_SERVER['DOCUMENT_ROOT'] . $custom404;

  if (file_exists($notFoundPath)) {
    readfile($notFoundPath);
  } else {
    echo '<h1>404 Not Found</h1><p>The page you are looking for does not exist.</p>';
  }

  logRequest($method, $uri, 404);
}

// Let the built-in server handle existing files but not directories.
if ($uri !== '/' && is_file($path)) {
  logRequest($method, $uri, 200);
  return false;
}

// Serve index.html for directory-based routes like /blog/my-post
if ($uri !== '/' && is_dir($path)) {
  $indexFile = rtrim($path, '/\\') . '/index.html';
  if (file_exists($indexFile)) {
    http_response_code(200);
    readfile($indexFile);
    logRequest($method, $uri, 200);
    return true;
  }
  serveNotFound($method, $uri);
  return true;
}

// PHP does not automatically serve index.html for the root path
if ($uri === '/' || $uri === '') {
  http_response_code(200);
  readfile($_SERVER['DOCUMENT_ROOT'] . '/index.html');
  logRequest($method, $uri, 200);
  return true;
}


// Serve custom 404
serveNotFound($method, $uri);
