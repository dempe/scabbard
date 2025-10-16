<?php

namespace Scabbard\Support;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Sabberworm\CSS\Parser as CssParser;
use Sabberworm\CSS\Value\CSSString;
use Sabberworm\CSS\Value\URL;

class Fingerprinter
{
    /**
     * Fingerprint configured files in the output directory and update references.
     *
     * @param callable(string):void|null $infoLogger
     * @param callable(string):void|null $errorLogger
     */
    public function fingerprint(string $outputPath, ?callable $infoLogger = null, ?callable $errorLogger = null): void
    {
        $patterns = Config::get('scabbard.fingerprint', []);
        if ($patterns === [] || $patterns === null || ! is_array($patterns)) {
            return;
        }

        $this->log($infoLogger, 'Fingerprint patterns: ' . json_encode($patterns));

        $fingerprinted = [];

        foreach (File::allFiles($outputPath) as $file) {
            $relative = ltrim(str_replace($outputPath, '', $file->getPathname()), DIRECTORY_SEPARATOR);
            $relative = str_replace(DIRECTORY_SEPARATOR, '/', $relative);

            foreach ($patterns as $pattern) {
                $regex = $this->patternToRegex($pattern);
                if (preg_match($regex, $relative) === 1) {
                    $hash = substr((string) sha1_file($file->getPathname()), 0, 8);
                    $info = pathinfo($file->getPathname());
                    $newName = $info['filename'] . '.' . $hash . (isset($info['extension']) ? '.' . $info['extension'] : '');
                    $newPath = ($info['dirname'] ?? dirname($file->getPathname())) . DIRECTORY_SEPARATOR . $newName;
                    File::move($file->getPathname(), $newPath);
                    $fingerprinted[$relative] = str_replace(DIRECTORY_SEPARATOR, '/', ltrim(str_replace($outputPath, '', $newPath), DIRECTORY_SEPARATOR));
                    $fingerprinted['/' . $relative] = '/' . $fingerprinted[$relative];
                    $fingerprinted['./' . $relative] = './' . $fingerprinted[$relative];
                    break;
                }
            }
        }

        if ($fingerprinted === []) {
            $this->log($infoLogger, 'No files matched fingerprint patterns. Check your config.');
            return;
        }

        foreach (File::allFiles($outputPath) as $file) {
            if ($file->getExtension() !== 'html') {
                continue;
            }

            $contents = File::get($file->getPathname());

            $dom = new \DOMDocument();
            $libxmlPrevious = libxml_use_internal_errors(true);
            $dom->loadHTML($contents, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            libxml_clear_errors();
            libxml_use_internal_errors($libxmlPrevious);

            $xpath = new \DOMXPath($dom);
            $updated = false;
            $nodes = $xpath->query('//@href | //@src');
            if ($nodes !== false) {
                foreach ($nodes as $attr) {
                    if (! $attr instanceof \DOMAttr) {
                        continue;
                    }

                    $value = $attr->value;
                    $parts = parse_url($value);
                    $path = $parts['path'] ?? $value;

                    if (! array_key_exists($path, $fingerprinted)) {
                        continue;
                    }

                    $new = $fingerprinted[$path];
                    if (isset($parts['query'])) {
                        $new .= '?' . $parts['query'];
                    }
                    if (isset($parts['fragment'])) {
                        $new .= '#' . $parts['fragment'];
                    }

                    $attr->value = $new;
                    $updated = true;
                }
            }

            if ($updated) {
                File::put($file->getPathname(), (string) $dom->saveHTML());
            }
        }

        foreach (File::allFiles($outputPath) as $file) {
            if ($file->getExtension() !== 'css') {
                continue;
            }

            $cssRelative = ltrim(str_replace($outputPath, '', $file->getPathname()), DIRECTORY_SEPARATOR);
            $cssRelative = str_replace(DIRECTORY_SEPARATOR, '/', $cssRelative);
            $cssDir = dirname($cssRelative);

            try {
                $parser = new CssParser(File::get($file->getPathname()));
                $document = $parser->parse();
            } catch (\Throwable $e) {
                $this->log($errorLogger, 'CSS parsing failed for ' . $cssRelative . ': ' . $e->getMessage());
                continue;
            }

            $updated = false;

            foreach ($document->getAllValues() as $value) {
                if (! $value instanceof URL) {
                    continue;
                }

                $original = $value->getURL()->getString();
                $parts = parse_url($original);
                $path = $parts['path'] ?? $original;
                if ($path === '') {
                    continue;
                }

                $normalized = $path;
                if ($path[0] !== '/') {
                    $combined = $cssDir === '.' ? $path : $cssDir . '/' . $path;
                    $normalized = str_replace('\\', '/', $combined);
                    $segments = [];
                    foreach (explode('/', $normalized) as $segment) {
                        if ($segment === '' || $segment === '.') {
                            continue;
                        }
                        if ($segment === '..') {
                            array_pop($segments);
                            continue;
                        }
                        $segments[] = $segment;
                    }
                    $normalized = implode('/', $segments);
                } else {
                    $normalized = ltrim($path, '/');
                }

                if (! array_key_exists($normalized, $fingerprinted)) {
                    if (array_key_exists('/' . $normalized, $fingerprinted)) {
                        $normalized = '/' . $normalized;
                    } elseif (array_key_exists('./' . $normalized, $fingerprinted)) {
                        $normalized = './' . $normalized;
                    } else {
                        continue;
                    }
                }

                $new = '/' . ltrim($fingerprinted[$normalized], '/');
                if (isset($parts['query'])) {
                    $new .= '?' . $parts['query'];
                }
                if (isset($parts['fragment'])) {
                    $new .= '#' . $parts['fragment'];
                }

                $value->setURL(new CSSString($new));
                $updated = true;
            }

            if ($updated) {
                File::put($file->getPathname(), $document->render());
            }
        }
    }

    public function patternToRegex(string $pattern): string
    {
        $pattern = str_replace(DIRECTORY_SEPARATOR, '/', $pattern);
        $regex = preg_quote($pattern, '#');
        $regex = str_replace('\\*\\*', '.*', $regex);
        $regex = str_replace('\\*', '[^/]*', $regex);
        $regex = str_replace('\\?', '.', $regex);

        return '#^' . $regex . '$#';
    }

    /**
     * @param callable(string):void|null $logger
     */
    protected function log(?callable $logger, string $message): void
    {
        if ($logger !== null) {
            $logger($message);
        }
    }
}
