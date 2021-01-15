<?php

declare(strict_types = 1);

namespace Drupal\joinup_search\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\joinup_community_content\CommunityContentHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an autocomplete filter plugin for specific bundles.
 */
class BundleAutocompleteDeriver extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  private $entityTypeBundleInfo;

  /**
   * Instantiates a new BundleAutocompleteDeriver class.
   *
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   */
  public function __construct(EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition): array {
    $this->derivatives = [];

    foreach ($this->bundleMap() as $entity_type_id => $info) {
      $bundle_list = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);

      foreach ($info['allowed_bundles'] as $bundle_id) {
        $definition = [
          'fields' => [$info['solr_field_name']],
          'entity_type_id' => $entity_type_id,
          'bundle' => $bundle_id,
          'label' => $bundle_list[$bundle_id]['label'],
        ];

        $this->derivatives["{$entity_type_id}:{$bundle_id}"] = $definition + $base_plugin_definition;
      }
    }

    return $this->derivatives;
  }

  /**
   * Returns an array of data regarding referenceable bundles.
   *
   * Each element has for key an entity type id, and its value consist of:
   * - solr_field_name: the name of the field configured in search API to hold
   *   the id information.
   * - allowed_bundles: which bundles are allowed to be referenced.
   *
   * @return array
   *   A list of bundles and solr field name, keyed by entity type id.
   */
  protected function bundleMap(): array {
    return [
      'rdf_entity' => [
        'solr_field_name' => 'id',
        'allowed_bundles' => [
          'solution',
        ],
      ],
      'node' => [
        'solr_field_name' => 'nid',
        'allowed_bundles' => CommunityContentHelper::BUNDLES,
      ],
    ];
  }

}
