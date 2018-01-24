<?php

namespace Drupal\joinup_subscription\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'subscription_bundle' field type.
 *
 * @FieldType(
 *   id = "subscription_bundle",
 *   label = @Translation("Subscription bundle"),
 *   description = @Translation("Bundles the user is subscribed to") * )
 */
class SubscriptionBundle extends FieldItemBase {

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
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [
      'columns' => [
        'entity_type' => [
          'type' => 'varchar',
          'length' => 50,
        ],
        'bundle' => [
          'type' => 'varchar',
          'length' => 50,
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

}
