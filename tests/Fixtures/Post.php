<?php

namespace Scabbard\Tests\Fixtures;

use Illuminate\Support\Collection;

/**
 * Dummy model used for testing dynamic route value resolution.
 */

class Post
{
  /**
   * @return Collection<int, string>
   */
  public static function pluck(string $attribute): Collection
  {
    return collect(['alpha', 'beta']);
  }
}
