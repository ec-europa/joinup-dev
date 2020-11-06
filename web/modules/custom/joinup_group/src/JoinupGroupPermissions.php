<?php

declare(strict_types = 1);

namespace Drupal\joinup_group;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\rdf_entity\Entity\RdfEntityType;

/**
 * Dynamic permissions provider.
 */
class JoinupGroupPermissions {

  use StringTranslationTrait;

  /**
   * Returns an array of group ownership permissions.
   *
   * @return array
   *   Dynamically generated permissions.
   */
  public function groupOwnershipPermissions() {
    $permissions = [];
    /** @var \Drupal\og\GroupTypeManagerInterface $group_type_manager */
    $group_type_manager = \Drupal::service('og.group_type_manager');
    $bundle_ids = $group_type_manager->getGroupBundleIdsByEntityType('rdf_entity');
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
