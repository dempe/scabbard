<?php

if (!defined('STDOUT')) {
    define('STDOUT', fopen('php://stdout', 'w'));
}

$requestUri = $_SERVER['REQUEST_URI'];
$method     = $_SERVER['REQUEST_METHOD'];
$docRoot    = $_SERVER['DOCUMENT_ROOT'];
$path       = parse_url($requestUri, PHP_URL_PATH);
$fullPath   = realpath($docRoot . $path);

function serveNotFound(string $method, string $uri): void
{
    http_response_code(404);
    header('Content-Type: text/html; charset=utf-8');
    $custom404 = getenv('SCABBARD_NOT_FOUND') ?: '/404.html';
    $notFoundPath = $_SERVER['DOCUMENT_ROOT'] . $custom404;

    if (file_exists($notFoundPath)) {
        readfile($notFoundPath);
    } else {
        echo '<h1>404 Not Found</h1><p>The page you are looking for does not exist.</p>';
    }

    flush();
    exit;
}

// Bail out if fullPath is invalid or outside doc root
if ($fullPath === false || strpos($fullPath, $docRoot) !== 0) {
    serveNotFound($method, $requestUri);
    return true;
}

// Static file exists and is not a directory
if ($requestUri !== '/' && is_file($fullPath)) {
    return false; // Let PHP's built-in server serve it
}

// Directory route like /blog/my-post/
if ($requestUri !== '/' && is_dir($fullPath)) {
    $indexFile = rtrim($fullPath, '/\\') . '/index.html';
    if (file_exists($indexFile)) {
        http_response_code(200);
        header('Content-Type: text/html; charset=utf-8');
        readfile($indexFile);
        return true;
    }
    serveNotFound($method, $requestUri);
    return true;
}

// Root path
if ($requestUri === '/' || $requestUri === '') {
    http_response_code(200);
    header('Content-Type: text/html; charset=utf-8');
    readfile($docRoot . '/index.html');
    return true;
}

// Fallback
serveNotFound($method, $requestUri);
