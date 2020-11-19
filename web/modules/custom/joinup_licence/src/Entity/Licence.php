<?php

declare(strict_types = 1);

namespace Drupal\joinup_licence\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
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
  public function getCompatibilityDocumentId(LicenceInterface $outbound_licence): string {
    /** @var \Drupal\joinup_licence\JoinupLicenceCompatibilityRulePluginManager $plugin_manager */
    $plugin_manager = \Drupal::service('plugin.manager.joinup_licence_compatibility_rule');
    return $plugin_manager->getCompatibilityDocumentId($this, $outbound_licence);
  }

  /**
   * {@inheritdoc}
   */
  public function getCompatibilityDocument(LicenceInterface $outbound_licence): CompatibilityDocumentInterface {
    $compatibility_document_id = $this->getCompatibilityDocumentId($outbound_licence);
    return CompatibilityDocument::load($compatibility_document_id)
      ->setUseLicence($this)
      ->setRedistributeAsLicence($outbound_licence);
  }

  /**
   * {@inheritdoc}
   */
  public static function loadBySpdxId(string $spdx_id): ?LicenceInterface {
    try {
      $storage = \Drupal::entityTypeManager()->getStorage('rdf_entity');
    }
    catch (\Exception $e) {
      \Drupal::logger('joinup_licence')->error($e->getMessage());
      return NULL;
    }

    if (!$storage instanceof EntityStorageInterface) {
      \Drupal::logger('joinup_licence')->error('Retrieval of RDF storage failed.');
      return NULL;
    }

    // Retrieve the ID of the SPDX licence entity.
    $spdx_entity_ids = $storage->getQuery()
      ->condition('rid', 'spdx_licence')
      ->condition('field_spdx_licence_id', $spdx_id)
      ->execute();

    if (empty($spdx_entity_ids)) {
      return NULL;
    }

    // Retrieve the ID of the licence that references the SPDX licence entity.
    $spdx_entity_id = reset($spdx_entity_ids);
    $licence_ids = $storage->getQuery()
      ->condition('rid', 'licence')
      ->condition('field_licence_spdx_licence', $spdx_entity_id)
      ->execute();

    if (empty($licence_ids)) {
      return NULL;
    }

    $licence_id = reset($licence_ids);
    $licence = $storage->load($licence_id);
    if (!$licence instanceof LicenceInterface) {
      return NULL;
    }

    return $licence;
  }

}
