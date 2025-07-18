<?php

/**
 *  Router script used by `scabbard:serve` to provide a custom 404 page.
 *
 * The script checks if the requested resource exists within the document root.
 * If so, it delegates to the built-in PHP server. Otherwise it serves the
 * generated `404.html` file with a 404 status code.
 */
$requestUri = $_SERVER["REQUEST_URI"];
$method     = $_SERVER["REQUEST_METHOD"];
$status     = 200;
$docRoot    = $_SERVER["DOCUMENT_ROOT"];
$path       = parse_url($requestUri, PHP_URL_PATH);
$file       = realpath($docRoot . $path);

if (!defined('STDOUT')) {
  define('STDOUT', fopen('php://stdout', 'w'));
}

function logRequest(string $method, string $path, int $status): void
{
  $time = date('d/M/Y:H:i:s');
  $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
  fprintf(STDOUT, "%s - - [%s] \"%s %s\" %d\n", $ip, $time, $method, $path, $status);
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
if ($requestUri !== '/' && is_file($path)) {
  logRequest($method, $requestUri, 200);
  return false;
}

// Serve index.html for directory-based routes like /blog/my-post
if ($requestUri !== '/' && is_dir($path)) {
  $indexFile = rtrim($path, '/\\') . '/index.html';
  if (file_exists($indexFile)) {
    http_response_code(200);
    readfile($indexFile);
    logRequest($method, $requestUri, 200);
    return true;
  }
  serveNotFound($method, $requestUri);
  return true;
}

// PHP does not automatically serve index.html for the root path
if ($requestUri === '/' || $requestUri === '') {
  http_response_code(200);
  readfile($_SERVER['DOCUMENT_ROOT'] . '/index.html');
  logRequest($method, $requestUri, 200);
  return true;
}


// Serve custom 404
serveNotFound($method, $requestUri);
