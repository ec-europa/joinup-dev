<?php

declare(strict_types = 1);

namespace Drupal\collection\Entity;

use Drupal\joinup_bundle_class\JoinupBundleClassFieldAccessTrait;
use Drupal\joinup_bundle_class\ShortIdTrait;
use Drupal\joinup_workflow\EntityWorkflowStateTrait;
use Drupal\rdf_entity\Entity\Rdf;

/**
 * Entity subclass for the 'collection' bundle.
 */
class Collection extends Rdf implements CollectionInterface {

  use EntityWorkflowStateTrait;
  use JoinupBundleClassFieldAccessTrait;
  use ShortIdTrait;

  /**
   * {@inheritdoc}
   */
  public function getSolutionIds(): array {
    $ids = $this->getReferencedEntityIds('field_ar_affiliates');
    return $ids['rdf_entity'] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function getWorkflowStateFieldName(): string {
    return 'field_ar_state';
  }

}
