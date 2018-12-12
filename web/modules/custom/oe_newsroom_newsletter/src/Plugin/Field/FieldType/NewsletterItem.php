<?php

declare(strict_types = 1);

namespace Drupal\oe_newsroom_newsletter\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\Exception\MissingDataException;

/**
 * Defines the field type for OpenEuropa Newsroom Newsletter fields.
 *
 * @FieldType(
 *   id = "oe_newsroom_newsletter",
 *   label = @Translation("Newsroom newsletter"),
 *   description = @Translation("Stores the configuration for a Newsroom newsletter."),
 *   default_widget = "oe_newsroom_newsletter_default",
 *   default_formatter = "oe_newsroom_newsletter_subscribe_form"
 * )
 */
class NewsletterItem extends FieldItemBase implements NewsletterItemInterface {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    return [
      'enabled' => DataDefinition::create('boolean')
        ->setLabel(t('Enable newsletter subscriptions')),
      'universe' => DataDefinition::create('string')
        ->setLabel(t('The Newsroom universe acronym')),
      'service_id' => DataDefinition::create('integer')
        ->setLabel(t('The Newsroom newsletter service ID')),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'enabled' => [
          'description' => 'Whether or not subscribing to newsletters is currently enabled.',
          'type' => 'int',
          'unsigned' => TRUE,
          'size' => 'tiny',
        ],
        'universe' => [
          'description' => 'The Newsroom universe acronym.',
          'type' => 'varchar',
          'length' => 100,
        ],
        'service_id' => [
          'description' => 'The Newsroom service ID.',
          'type' => 'int',
          'unsigned' => TRUE,
          'size' => 'normal',
        ],
      ],
      'indexes' => [
        'universe' => ['universe'],
        'service_id' => ['service_id'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $random = new Random();
    $values = [
      'enabled' => TRUE,
      'universe' => $random->word(mt_rand(1, 50)),
      'service_id' => mt_rand(1, 1000),
    ];
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    try {
      $universe = $this->get('universe')->getValue();
    }
    catch (MissingDataException $e) {
      return TRUE;
    }

    try {
      $service_id = $this->get('service_id')->getValue();
    }
    catch (MissingDataException $e) {
      return TRUE;
    }

    return $universe === NULL || $service_id === NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getUniverse(): ?string {
    try {
      return $this->get('universe')->getValue();
    }
    catch (MissingDataException $e) {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getServiceId(): ?int {
    try {
      $service_id = $this->get('service_id')->getValue();
      if (!empty($service_id)) {
        return (int) $service_id;
      }
      return NULL;
    }
    catch (MissingDataException $e) {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(): bool {
    try {
      return !$this->isEmpty() && (bool) $this->get('enabled')->getValue();
    }
    catch (MissingDataException $e) {
      return FALSE;
    }
  }

}
