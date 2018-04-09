<?php

namespace Drupal\joinup_core;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\rdf_entity\Entity\RdfEntityType;

/**
 * Dynamic permissions provider.
 */
class JoinupPermissions {

  use StringTranslationTrait;

  /**
   * Returns an array of group ownership permissions.
   *
   * @return array
   *   Dynamically generated permissions.
   */
  public function groupOwnershipPermissions() {
    $permissions = [];
    $bundle_ids = \Drupal::service('og.group_type_manager')->getAllGroupBundles('rdf_entity');
    /** @var \Drupal\rdf_entity\RdfEntityTypeInterface[] $bundles */
    $bundles = RdfEntityType::loadMultiple($bundle_ids);
    foreach ($bundles as $bundle_id => $bundle) {
      $permissions["administer $bundle_id ownership"] = [
        'title' => $this->t('Administer @plural_label ownership', ['@plural_label' => $bundle->getPluralLabel()]),
        'description' => $this->t('Allows users granted with this permission to transfer the @singular_label ownership.', ['@singular_label' => $bundle->getSingularLabel()]),
      ];
    }

    return $permissions;
  }

}
