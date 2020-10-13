<?php

declare(strict_types = 1);

namespace Drupal\joinup_group;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\og\GroupTypeManagerInterface;
use Drupal\rdf_entity\Entity\RdfEntityType;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Dynamic permissions provider.
 */
class JoinupGroupPermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The OG group type manager.
   *
   * @var \Drupal\og\GroupTypeManagerInterface
   */
  protected $groupTypeManager;

  /**
   * Creates a new JoinupGroupPermissions object.
   *
   * @param \Drupal\og\GroupTypeManagerInterface $groupTypeManager
   *   The OG group type manager.
   */
  public function __construct(GroupTypeManagerInterface $groupTypeManager) {
    $this->groupTypeManager = $groupTypeManager;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('og.group_type_manager')
    );
  }

  /**
   * Returns an array of group ownership permissions.
   *
   * @return array
   *   Dynamically generated permissions.
   */
  public function groupOwnershipPermissions() {
    $permissions = [];
    $bundle_ids = $this->groupTypeManager->getGroupBundleIdsByEntityType('rdf_entity');
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
