<?php

declare(strict_types = 1);

namespace Drupal\joinup_licence\Entity;

use Drupal\joinup_bundle_class\JoinupBundleClassFieldAccessTrait;
use Drupal\rdf_taxonomy\Entity\RdfTerm;

/**
 * Bundle class for the 'legal_type' taxonomy term.
 */
class LicenceLegalType extends RdfTerm implements LicenceLegalTypeInterface {

  use JoinupBundleClassFieldAccessTrait;

  /**
   * {@inheritdoc}
   */
  public function getCategory(): ?LicenceLegalTypeInterface {
    // The legal type vocabulary is a strict two level hierarchy, we can trust
    // that there is one parent, or no parent.
    $category = $this->getFirstReferencedEntity('parent');
    if ($category instanceof LicenceLegalTypeInterface) {
      return $category;
    }

    return NULL;
  }

}
