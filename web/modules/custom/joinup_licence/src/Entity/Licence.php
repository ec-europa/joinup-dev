<?php

declare(strict_types = 1);

namespace Drupal\joinup_licence\Entity;

use Drupal\joinup_bundle_class\JoinupBundleClassFieldAccessTrait;
use Drupal\rdf_entity\Entity\Rdf;

/**
 * Entity subclass for the 'solution' bundle.
 */
class Licence extends Rdf implements LicenceInterface {

  use JoinupBundleClassFieldAccessTrait;

  /**
   * {@inheritdoc}
   */
  public function getSpdxLicenceRdfId(): ?string {
    if ($id = $this->getMainPropertyValue('field_licence_spdx_licence')) {
      return (string) $id;
    }
    return NULL;
  }

}
