<?php

declare(strict_types = 1);

namespace Drupal\joinup_licence\Entity;

use Drupal\joinup_bundle_class\JoinupBundleClassFieldAccessTrait;
use Drupal\rdf_entity\Entity\Rdf;

/**
 * Bundle class for the 'licence' bundle.
 */
class Licence extends Rdf implements LicenceInterface {

  use JoinupBundleClassFieldAccessTrait;

  /**
   * {@inheritdoc}
   */
  public function getSpdxLicenceEntity(): ?SpdxLicenceInterface {
    $spdx_licence = $this->getFirstReferencedEntity('field_licence_spdx_licence');
    if ($spdx_licence instanceof SpdxLicenceInterface) {
      return $spdx_licence;
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getSpdxLicenceId(): ?string {
    if ($spdx_licence = $this->getSpdxLicenceEntity()) {
      return $spdx_licence->getLicenceId();
    }

    return NULL;
  }

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
