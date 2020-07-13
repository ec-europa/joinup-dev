<?php

declare(strict_types = 1);

namespace Drupal\asset_release\Entity;

use Drupal\joinup_bundle_class\JoinupBundleClassFieldAccessTrait;
use Drupal\joinup_group\Entity\GroupInterface;
use Drupal\joinup_group\Exception\MissingGroupException;
use Drupal\joinup_workflow\EntityWorkflowStateTrait;
use Drupal\rdf_entity\Entity\Rdf;
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
  public function getGroup(): GroupInterface {
    /** @var \Drupal\joinup_group\Entity\GroupInterface[] $groups */
    $groups = $this->getReferencedEntities('field_isr_is_version_of');
    if (empty($groups)) {
      throw new MissingGroupException();
    }

    return reset($groups);
  }

  /**
   * {@inheritdoc}
   */
  public function getWorkflowStateFieldName(): string {
    return 'field_isr_state';
  }

}
