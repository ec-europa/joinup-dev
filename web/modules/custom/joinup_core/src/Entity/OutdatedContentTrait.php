<?php

declare(strict_types = 1);

namespace Drupal\joinup_core\Entity;

/**
 * Reusable code for classed implementing OutdatedContentInterface interface.
 *
 * @see \Drupal\joinup_core\Entity\OutdatedContentInterface
 */
trait OutdatedContentTrait {

  /**
   * {@inheritdoc}
   */
  public function getOutdatedTime(): ?int {
    assert($this instanceof OutdatedContentInterface, 'Class ' . get_class($this) . ' must implement ' . OutdatedContentInterface::class);
    if ($this->hasField('outdated_time')) {
      return $this->get('outdated_time')->first()->getValue()['value'];
    }
    return NULL;
  }

}
