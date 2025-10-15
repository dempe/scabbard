<?php

namespace Scabbard\Content;

use Illuminate\Support\Facades\File;
use Spatie\YamlFrontMatter\YamlFrontMatter;

class FrontMatterParser
{
    /**
     * Parse YAML front matter for all files in the provided directories.
     *
     * @param array<int, string> $directories
     * @return array<string, \Spatie\YamlFrontMatter\Document>
     */
    public function parse(array $directories): array
    {
        $documents = [];

        foreach ($directories as $directory) {
            if (! File::isDirectory($directory)) {
                continue;
            }

            foreach (File::allFiles($directory) as $file) {
                if (! $file->isFile()) {
                    continue;
                }

                $path = $file->getPathname();

                try {
                    $documents[$path] = YamlFrontMatter::parseFile($path);
                } catch (\Throwable) {
                    // Skip files that cannot be parsed. They will be reported by callers if needed.
                }
            }
        }

        return $documents;
    }
}
