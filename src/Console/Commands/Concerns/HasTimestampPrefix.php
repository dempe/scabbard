<?php

namespace Scabbard\Console\Commands\Concerns;

trait HasTimestampPrefix
{
  protected function timestampPrefix(): string
  {
    return '[' . now()->format('H:i:s') . '] ';
  }
}
