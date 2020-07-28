<?php

declare(strict_types = 1);

namespace Drupal\solution\Entity;

use Drupal\collection\Entity\CollectionInterface;
use Drupal\collection\Exception\MissingCollectionException;
use Drupal\joinup_bundle_class\JoinupBundleClassFieldAccessTrait;
use Drupal\joinup_bundle_class\ShortIdTrait;
use Drupal\joinup_group\Entity\GroupInterface;
use Drupal\joinup_group\Entity\GroupTrait;
use Drupal\joinup_group\Exception\MissingGroupException;
use Drupal\joinup_workflow\EntityWorkflowStateTrait;
use Drupal\rdf_entity\Entity\Rdf;

/**
 * Entity subclass for the 'solution' bundle.
 */
class Solution extends Rdf implements SolutionInterface {

  use EntityWorkflowStateTrait;
  use GroupTrait;
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
  public function getGroup(): GroupInterface {
    $field_item = $this->getFirstItem('collection');
    if (!$field_item || $field_item->isEmpty()) {
      throw new MissingGroupException();
    }
    $collection = $field_item->entity;
    if (empty($collection)) {
      // The collection entity can be empty in case it has been deleted and the
      // affiliated solutions have not yet been garbage collected.
      throw new MissingGroupException();
    }
    return $collection;
  }

  /**
   * {@inheritdoc}
   */
  public function getWorkflowStateFieldName(): string {
    return 'field_is_state';
  }

}
