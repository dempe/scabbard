<?php

namespace Scabbard\Tests\Unit;

use Illuminate\Support\Facades\File;
use Scabbard\Content\FrontMatterParser;
use Scabbard\Tests\TestCase;

class FrontMatterParserTest extends TestCase
{
    public function test_it_parses_yaml_front_matter_from_configured_directories(): void
    {
        $tempContentDir = base_path('tests/tmp_content');
        File::deleteDirectory($tempContentDir);
        File::ensureDirectoryExists($tempContentDir);

        $postPath = $tempContentDir . DIRECTORY_SEPARATOR . 'post.md';
        File::put($postPath, <<<MD
---
title: Example Post
draft: false
---

Content body.
MD);

        $parser = new FrontMatterParser();
        $documents = $parser->parse([$tempContentDir]);

        $this->assertArrayHasKey($postPath, $documents);
        $this->assertSame('Example Post', $documents[$postPath]->matter('title'));
        $this->assertFalse($documents[$postPath]->matter('draft'));

        File::deleteDirectory($tempContentDir);
    }
}
