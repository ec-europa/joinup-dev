<?php

declare(strict_types = 1);

namespace Drupal\joinup_licence\Entity;

use Drupal\joinup_bundle_class\JoinupBundleClassFieldAccessTrait;
use Drupal\rdf_entity\Entity\Rdf;

/**
 * Bundle class for the 'SPDX' bundle.
 *
 * This RDF entity bundle type is defined by the SPDX module, but since the
 * bundle classes are not yet available in vanilla Drupal we define them inside
 * Joinup rather than in the contributed module.
 */
class SpdxLicence extends Rdf implements SpdxLicenceInterface {

  use JoinupBundleClassFieldAccessTrait;

  /**
   * {@inheritdoc}
   */
  public function getLicenceId(): ?string {
    if ($id = $this->getMainPropertyValue('field_spdx_licence_id')) {
      return (string) $id;
    }
    return NULL;
  }

}
