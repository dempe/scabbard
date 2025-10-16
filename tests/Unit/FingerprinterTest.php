<?php

namespace Scabbard\Tests\Unit;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Scabbard\Support\Fingerprinter;
use Scabbard\Tests\TestCase;

class FingerprinterTest extends TestCase
{
    public function test_pattern_to_regex_supports_glob_syntax(): void
    {
        $fingerprinter = new Fingerprinter();

        $regex = $fingerprinter->patternToRegex('assets/**/*.css');

        $this->assertSame(1, preg_match($regex, 'assets/css/app.css'));
        $this->assertSame(1, preg_match($regex, 'assets/vendor/css/app.css'));
        $this->assertSame(0, preg_match($regex, 'assets/js/app.js'));
    }

    public function test_fingerprint_renames_files_and_updates_references(): void
    {
        $outputDir = base_path('tests/tmp_fingerprint_output');

        File::deleteDirectory($outputDir);
        File::ensureDirectoryExists($outputDir . '/assets/css');
        File::ensureDirectoryExists($outputDir . '/assets/fonts');

        File::put($outputDir . '/assets/fonts/foo.woff', 'dummyfont');
        File::put($outputDir . '/assets/css/app.css', '@font-face { src: url("../fonts/foo.woff"); }');
        File::put($outputDir . '/index.html', '<link rel="stylesheet" href="/assets/css/app.css">');

        Config::set('scabbard.fingerprint', ['assets/fonts/*.woff', 'assets/css/*.css']);

        $messages = [];
        $fingerprinter = new Fingerprinter();
        $fingerprinter->fingerprint(
            $outputDir,
            function (string $message) use (&$messages): void {
                $messages[] = $message;
            }
        );

        $fingerprintedFiles = collect(File::allFiles($outputDir));

        $css = $fingerprintedFiles->first(function ($file) {
            return $file->getExtension() === 'css';
        });
        $font = $fingerprintedFiles->first(function ($file) {
            return $file->getExtension() === 'woff';
        });

        $this->assertNotNull($css);
        $this->assertNotNull($font);
        $this->assertStringNotContainsString('app.css', $css->getFilename());
        $this->assertStringNotContainsString('foo.woff', $font->getFilename());
        $this->assertStringContainsString('Fingerprint patterns:', $messages[0] ?? '');

        $html = File::get($outputDir . '/index.html');
        $this->assertStringContainsString($css->getFilename(), $html);

        $cssContents = File::get($css->getPathname());
        $this->assertStringContainsString($font->getFilename(), $cssContents);

        Config::set('scabbard.fingerprint', []);
        File::deleteDirectory($outputDir);
    }
}
