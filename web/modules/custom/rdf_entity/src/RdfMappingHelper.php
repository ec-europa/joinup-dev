<?php

namespace Drupal\rdf_entity;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\rdf_entity\Entity\RdfEntitySparqlStorage;

/**
 * Contains helper methods that help with the uri mappings of Drupal elements.
 *
 * @package Drupal\rdf_entity
 */
class RdfMappingHelper {
  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $moduleHandler;

  /**
   * Constructs a QueryFactory object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *    The entity type manager.
   */
  public function __construct(EntityTypeManager $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $this->getModuleHandlerService();
  }

  /**
   * Returns all bundle key mappings of the passed rdf entity type.
   *
   * These mappings are the actual type of the bundle represented by an rdf
   * URI. This is not the predicate but the object.
   *
   * @param string $entity_type
   *    The machine name of the entity type.
   * @param string $bundle
   *    Optionally filter the mappings by bundle.
   *
   * @return array
   *    A list of bundle key mappings from all bundles of the passed entity
   *    type. The returned array is indexed by the bundle key.
   *
   * @throws \Exception
   *    Thrown when the rdf entity bundle has no mapped type uri.
   */
  public function getRdfBundleMappedUri($entity_type, $bundle = NULL) {
    $bundle_rdf_bundle_mapping = [];
    $storage = $this->entityTypeManager->getStorage($entity_type);

    $bundle_entities = empty($bundle) ? $storage->loadMultiple() : $storage->load($bundle);
    foreach ($bundle_entities as $bundle_entity) {
      // The id of the entity type is 'rdf_type' but the key ('id') is the
      // bundle key.
      $bundle_key = $bundle_entity->getEntityType()->getKey('id');
      $settings = $bundle_entity->getThirdPartySetting('rdf_entity', 'mapping_' . $bundle_key, FALSE);
      if (!is_array($settings)) {
        throw new \Exception('No rdf:type mapping set for bundle ' . $bundle_entity->label());
      }
      $type = array_pop($settings);
      $bundle_rdf_bundle_mapping[$bundle_entity->id()] = $type;
    }

    // Allow modules to interact and tamper with the passed list.
    $this->moduleHandler->alter('bundle_mapping', $bundle_rdf_bundle_mapping);
    return $bundle_rdf_bundle_mapping;
  }

  /**
   * Returns a list of bundle uris ready to be passed to a query as an array.
   *
   * @todo: This should return a simple array. A query helper method can convert
   *    it later on.
   *
   * @param string $entity_type
   *    The entity type of the bundles e.g. 'node_type'.
   * @param array $bundles
   *    Optionally filter and return only a subset of bundles.
   *
   * @return string
   *    A string including the converted array of bundle uris to a string value
   *    of a sparql array filter.
   */
  public function getBundleUriList($entity_type, $bundles = []) {
    $bundle_mapping = $this->getRdfBundleMappedUri($entity_type);
    if (empty($bundle_mapping)) {
      return;
    }

    $rdf_bundles = [];
    if (empty($bundles)) {
      $rdf_bundles = array_unique(array_values($bundle_mapping));
    }
    else {
      foreach ($bundles as $bundle) {
        if (isset($bundle_mapping[$bundle])) {
          $rdf_bundles[] = $bundle_mapping[$bundle];
        }
      }
    }

    return "(<" . implode(">, <", $rdf_bundles) . ">)";
  }

  /**
   * Returns the module handler service object.
   *
   * @todo: Check how we can inject this.
   */
  protected function getModuleHandlerService() {
    return \Drupal::moduleHandler();
  }
}