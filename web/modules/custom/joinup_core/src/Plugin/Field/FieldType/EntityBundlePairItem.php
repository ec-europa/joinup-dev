<?php

namespace Drupal\joinup_core\Plugin\Field\FieldType;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'entity_bundle' field type.
 *
 * This is a pseudo reference field that references bundles by storing the
 * entity type and the bundle machine name.
 *
 * @FieldType(
 *   id = "entity_bundle_pair",
 *   label = @Translation("Entity bundle pair"),
 *   description = @Translation("A simple field referencing to bundles")
 * )
 */
class EntityBundlePairItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['entity_type'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Entity type'))
      ->setRequired(TRUE);

    $properties['bundle'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Bundle'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * Returns the entity type ID of this entity bundle pair.
   *
   * @return string
   *   The entity type ID.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   *   Thrown when the data is not yet set.
   */
  public function getEntityTypeId(): string {
    return $this->get('entity_type')->getValue();
  }

  /**
   * Returns the bundle ID of this entity bundle pair.
   *
   * @return string
   *   The bundle ID.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   *   Thrown when the data is not yet set.
   */
  public function getBundleId(): string {
    return $this->get('bundle')->getValue();
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [
      'columns' => [
        'entity_type' => [
          'type' => 'varchar',
          'length' => EntityTypeInterface::ID_MAX_LENGTH,
        ],
        'bundle' => [
          'type' => 'varchar',
          'length' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
        ],
      ],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $entity_type = $this->get('entity_type')->getValue();
    $bundle = $this->get('bundle')->getValue();
    return empty($entity_type) || empty($bundle);
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'bundle';
  }

}
