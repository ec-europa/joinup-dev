<?php

declare(strict_types = 1);

namespace Drupal\solution\Entity;

use Drupal\collection\Entity\CollectionInterface;
use Drupal\collection\Exception\MissingCollectionException;
use Drupal\joinup_bundle_class\JoinupBundleClassFieldAccessTrait;
use Drupal\joinup_bundle_class\ShortIdTrait;
use Drupal\joinup_group\Exception\MissingGroupException;
use Drupal\joinup_workflow\EntityWorkflowStateTrait;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\rdf_entity\RdfInterface;

/**
 * Entity subclass for the 'solution' bundle.
 */
class Solution extends Rdf implements SolutionInterface {

  use EntityWorkflowStateTrait;
  use JoinupBundleClassFieldAccessTrait;
  use ShortIdTrait;

  /**
   * {@inheritdoc}
   */
  public function getCollection(): CollectionInterface {
    try {
      /** @var \Drupal\collection\Entity\CollectionInterface $group */
      $group = $this->getGroup();
    }
    catch (MissingGroupException $exception) {
      throw new MissingCollectionException($exception->getMessage(), 0, $exception);
    }
    return $group;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroup(): RdfInterface {
    $field_item = $this->getFirstItem('collection');
    if (!$field_item || $field_item->isEmpty()) {
      throw new MissingGroupException();
    }
    return $field_item->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getWorkflowStateFieldName(): string {
    return 'field_is_state';
  }

}
