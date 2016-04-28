<?php

namespace Drupal\joinup\Traits;

use Drupal\Component\Uuid\Php;

/**
 * Helper methods for generating random data in tests.
 */
trait RandomGeneratorTrait {

  /**
   * Returns a random URI ID for the collection.
   *
   * @return string
   *   A string URI
   */
  public function getRandomUri() {
    $php = new Php();
    return 'http://example.com/' . $php->generate();
  }

}
