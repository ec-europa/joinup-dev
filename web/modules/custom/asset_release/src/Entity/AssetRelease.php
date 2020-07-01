<?php

declare(strict_types = 1);

namespace Drupal\asset_release\Entity;

use Drupal\joinup_bundle_class\JoinupBundleClassFieldAccessTrait;
use Drupal\joinup_group\Exception\MissingGroupException;
use Drupal\joinup_workflow\EntityWorkflowStateTrait;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\rdf_entity\RdfInterface;
use Drupal\solution\Entity\SolutionContentTrait;

/**
 * Bundle class for the 'asset_release' bundle.
 */
class AssetRelease extends Rdf implements AssetReleaseInterface {

  use EntityWorkflowStateTrait;
  use JoinupBundleClassFieldAccessTrait;
  use SolutionContentTrait;

  /**
   * {@inheritdoc}
   */
  public function getGroup(): RdfInterface {
    $field_item = $this->getFirstItem('field_isr_is_version_of');
    if (!$field_item || $field_item->isEmpty()) {
      throw new MissingGroupException();
    }
    return $field_item->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getWorkflowStateFieldName(): string {
    return 'field_isr_state';
  }

}
