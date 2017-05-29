<?php

namespace Drupal\joinup\Traits;

/**
 * Helper methods to deal with entity references.
 */
trait EntityReferenceTrait {

  /**
   * Converts entity labels for entity reference fields to entity ids.
   *
   * @param string $entity_type
   *   The type of the entity being processed.
   * @param string $entity_bundle
   *   The bundle of the entity being processed.
   * @param array $values
   *   An array of field values keyed by field name.
   *
   * @return array
   *   The processed field values.
   *
   * @throws \Exception
   *   Thrown when no entity with the given label has been found.
   */
  public function convertEntityReferencesValues($entity_type, $entity_bundle, array $values) {
    /** @var \Drupal\Core\Field\FieldDefinitionInterface[] $definitions */
    $definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions($entity_type, $entity_bundle);
    foreach ($definitions as $name => $definition) {
      if ($definition->getType() != 'entity_reference' || !array_key_exists($name, $values) || empty($values[$name])) {
        continue;
      }

      // Retrieve the entity type and bundles that can be referenced.
      $settings = $definition->getSettings();
      $target_entity_type = $settings['target_type'];
      $target_entity_bundles = !empty($settings['handler_settings']['target_bundles']) ? $settings['handler_settings']['target_bundles'] : NULL;

      // Multi-value fields are separated by comma.
      foreach ($values[$name] as &$label) {
        $id = $this->getEntityIdByLabel($label, $target_entity_type, $target_entity_bundles);

        if (!$id) {
          $bundles = implode(',', $target_entity_bundles);
          throw new \Exception("Entity with label '$label' could not be found for '$target_entity_type ($bundles)' to fill field '$name'.");
        }

        $label = $id;
      }
    }

    return $values;
  }

  /**
   * Retrieves an entity by its label.
   *
   * @param string $label
   *   The label of the entity we are searching for.
   * @param string $entity_type
   *   The type of the entity.
   * @param array $entity_bundle
   *   The bundles to limit the search on. Optional.
   *
   * @return false|mixed
   *   The id of the found entity, false otherwise.
   */
  protected function getEntityIdByLabel($label, $entity_type, array $entity_bundle = []) {
    $label_key = \Drupal::entityTypeManager()->getDefinition($entity_type)->getKey('label');

    $query = \Drupal::entityQuery($entity_type)
      ->condition($label_key, $label)
      ->range(0, 1);

    if (!empty($entity_bundle)) {
      $bundle_key = \Drupal::entityTypeManager()->getDefinition($entity_type)->getKey('bundle');
      $query->condition($bundle_key, $entity_bundle, 'IN');
    }

    $result = $query->execute();

    return reset($result);
  }

}
