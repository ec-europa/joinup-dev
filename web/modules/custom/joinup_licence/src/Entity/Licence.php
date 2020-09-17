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
  public function getLegalTypes(): array {
    return $this->getReferencedEntities('field_licence_legal_type');
  }

  /**
   * {@inheritdoc}
   */
  public function hasLegalType(string $category_label, string $label): bool {
    foreach ($this->getLegalTypes() as $legal_type) {
      if ($legal_type->label() === $label) {
        if ($category = $legal_type->getCategory()) {
          if ($category->label() === $category_label) {
            return TRUE;
          }
        }
      }
    }

    return FALSE;
  }

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

  /**
   * {@inheritdoc}
   */
  public function isCompatibleWith(LicenceInterface $redistribute_as_licence): bool {
    return (bool) $this->getCompatibilityDocumentId($redistribute_as_licence);
  }

  /**
   * {@inheritdoc}
   */
  public function getCompatibilityDocumentId(LicenceInterface $redistribute_as_licence): ?string {
    /** @var \Drupal\joinup_licence\JoinupLicenceCompatibilityRulePluginManager $plugin_manager */
    $plugin_manager = \Drupal::service('plugin.manager.joinup_licence_compatibility_rule');
    return $plugin_manager->getCompatibilityDocumentId($this, $redistribute_as_licence);
  }

}
