<?php

declare(strict_types = 1);

namespace Drupal\joinup_group\Plugin\views;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Reusable code to build a flat list of bundles.
 */
trait BundleListTrait {

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * Static cache of bundle labels.
   *
   * @var string[]
   */
  protected $bundleLabels = [];

  /**
   * Constructs a new plugin instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * Returns the entity bundle label given a bundle ID.
   *
   * @param string $bundle
   *   The bundle.
   *
   * @throws \InvalidArgumentException
   *   If an invalid bundle has been passed.
   */
  protected function getBundleLabel(string $bundle): string {
    $bundle_labels = $this->getBundleLabels();
    if (!isset($bundle_labels[$bundle])) {
      throw new \InvalidArgumentException(print_r($bundle_labels, true). "Invalid '{$bundle}' bundle: not an 'rdf_entity' or 'node' bundle.");
    }
    return $bundle_labels[$bundle];
  }


  /**
   * Builds a flat list of bundle labels keyed by their bundle ID.
   *
   * @return string[]
   *   A flat list of bundle labels keyed by their bundle ID.
   */
  protected function getBundleLabels(): array {
    if (empty($this->bundleLabels)) {
      // We have no API to tell us what is the preferred order, thus we have to
      // hardcode the list of bundles in a human logic order.
      $bundles = [
        'solution' => 'rdf_entity',
        'asset_release' => 'rdf_entity',
        'asset_distribution' => 'rdf_entity',
        'news' => 'node',
        'event' => 'node',
        'document' => 'node',
        'discussion' => 'node',
        'custom_page' => 'node',
      ];
      foreach ($bundles as $bundle => $entity_type_id) {
        $this->bundleLabels[$bundle] = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id)[$bundle]['label'];
      }
    }
    return $this->bundleLabels;
  }

}
