<?php

declare(strict_types = 1);

namespace Drupal\solution\Entity;

use Drupal\collection\Entity\CollectionInterface;
use Drupal\collection\Exception\MissingCollectionException;
use Drupal\rdf_entity\Entity\Rdf;

/**
 * Entity subclass for the 'solution' bundle.
 */
class Solution extends Rdf implements SolutionInterface {

  /**
   * {@inheritdoc}
   */
  public function getCollection(): CollectionInterface {
    /** @var \Drupal\og\Plugin\Field\FieldType\OgStandardReferenceItem $audience_field */
    $audience_field = $this->getFirstItem('collection');
    if ($audience_field->isEmpty()) {
      throw new MissingCollectionException();
    }
    return $audience_field->entity;
  }

}
