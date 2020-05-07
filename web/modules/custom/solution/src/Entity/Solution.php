<?php

declare(strict_types = 1);

namespace Drupal\solution\Entity;

use Drupal\collection\Entity\CollectionInterface;
use Drupal\collection\Exception\MissingCollectionException;
use Drupal\joinup_bundle_class\JoinupBundleClassFieldAccessTrait;
use Drupal\rdf_entity\Entity\Rdf;

/**
 * Entity subclass for the 'solution' bundle.
 */
class Solution extends Rdf implements SolutionInterface {

  use JoinupBundleClassFieldAccessTrait;

  /**
   * {@inheritdoc}
   */
  public function getCollection(): CollectionInterface {
    $field_item = $this->getFirstItem('collection');
    if ($field_item->isEmpty()) {
      throw new MissingCollectionException();
    }
    return $field_item->entity;
  }

}
