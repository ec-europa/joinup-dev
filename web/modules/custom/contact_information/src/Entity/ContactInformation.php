<?php

declare(strict_types = 1);

namespace Drupal\contact_information\Entity;

use Drupal\joinup_bundle_class\JoinupBundleClassFieldAccessTrait;
use Drupal\joinup_workflow\EntityWorkflowStateTrait;
use Drupal\rdf_entity\Entity\Rdf;

/**
 * Bundle class for the 'contact_information' bundle.
 */
class ContactInformation extends Rdf implements ContactInformationInterface {

  use EntityWorkflowStateTrait;
  use JoinupBundleClassFieldAccessTrait;

  /**
   * {@inheritdoc}
   */
  public function getWorkflowStateFieldName(): string {
    return 'field_ci_state';
  }

}
