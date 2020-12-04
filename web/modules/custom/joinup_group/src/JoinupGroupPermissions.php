<?php

declare(strict_types = 1);

namespace Drupal\joinup_group;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\og\GroupTypeManagerInterface;
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
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

  /**
   * Creates a new JoinupGroupPermissions object.
   *
   * @param \Drupal\og\GroupTypeManagerInterface $groupTypeManager
   *   The OG group type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info
   *   The entity type bundle info service.
   */
  public function __construct(GroupTypeManagerInterface $groupTypeManager, EntityTypeBundleInfoInterface $bundle_info) {
    $this->groupTypeManager = $groupTypeManager;
    $this->bundleInfo = $bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('og.group_type_manager'),
      $container->get('entity_type.bundle.info')
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
    /** @var string[] $bundle_ids */
    $bundle_ids = $this->groupTypeManager->getGroupBundleIdsByEntityType('rdf_entity');
    $bundle_info = $this->bundleInfo->getBundleInfo('rdf_entity');
    foreach ($bundle_ids as $bundle_id) {
      $permissions["administer $bundle_id ownership"] = [
        'title' => $this->t('Administer @plural_label ownership', [
          '@plural_label' => $bundle_info[$bundle_id]['label_plural'],
        ]),
        'description' => $this->t('Allows users granted with this permission to transfer the @singular_label ownership.', [
          '@singular_label' => $bundle_info[$bundle_id]['label_singular'],
        ]),
      ];
    }

    return $permissions;
  }

}
