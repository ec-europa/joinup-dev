<?php

namespace Drupal\collection\Entity;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\rdf_entity\Entity\Rdf;

/**
 * Defines an rdf entity class.
 *
 * @ContentEntityType(
 *   id = "collection",
 *   label = @Translation("Collection rdf entity"),
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "bundle" = "rid"
 *   },
 *   handlers = {
 *     "storage" = "\Drupal\rdf_entity\Entity\RdfEntitySparqlStorage",
 *   },
 *
 * )
 */
class RdfCollection extends Rdf{
  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
    /** @var \Drupal\Core\Field\FieldDefinitionInterface[] $fields */
    $fields = parent::bundleFieldDefinitions($entity_type, $bundle, $base_field_definitions);

    if ($bundle == 'collection') {
      $fields['label'] = clone $base_field_definitions['label'];
      $fields['label']->setDescription('Custom description.');
    }
    return $fields;
  }
}
