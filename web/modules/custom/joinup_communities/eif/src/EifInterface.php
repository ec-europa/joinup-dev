<?php

declare(strict_types = 1);

namespace Drupal\eif;

/**
 * Provides an interface for 'eif.helper' service.
 */
interface EifInterface {

  /**
   * Returns the EIF categories.
   * @return array
   */
  public function getEifCategories(): array;

}
