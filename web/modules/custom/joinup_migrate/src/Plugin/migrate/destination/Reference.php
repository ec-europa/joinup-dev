<?php

namespace Drupal\joinup_migrate\Plugin\migrate\destination;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Plugin\migrate\destination\DestinationBase;
use Drupal\migrate\Plugin\MigrateDestinationPluginManager;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a destination plugin for references.
 *
 * @MigrateDestination(
 *   id = "reference"
 * )
 */
class Reference extends DestinationBase implements ContainerFactoryPluginInterface {

  /**
   * The destination plugin manager service.
   *
   * @var \Drupal\migrate\Plugin\MigrateDestinationPluginManager
   */
  protected $destinationPluginManager;

  /**
   * Static cache of destination plugin instances.
   *
   * @var \Drupal\migrate\Plugin\MigrateDestinationInterface[]
   */
  protected $plugin = [];

  /**
   * Static cache of entity keys, per entity type.
   *
   * @var array[]
   */
  protected $key = [];

  /**
   * Constructs an entity destination plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration.
   * @param \Drupal\migrate\Plugin\MigrateDestinationPluginManager $destination_plugin_manager
   *   The destination plugin manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, MigrateDestinationPluginManager $destination_plugin_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    $this->destinationPluginManager = $destination_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('plugin.manager.migrate.destination')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'entity_type_id' => [
        'type' => 'string',
      ],
      'entity_id' => [
        'type' => 'string',
        'length' => 2048,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields(MigrationInterface $migration = NULL) {
    return [
      'entity_type_id' => $this->t('Entity type'),
      'bundle' => $this->t('Bundle'),
      'entity_id' => $this->t('Entity ID'),
      'values' => $this->t('Values'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    $entity_type_id = $row->getDestinationProperty('entity_type_id');
    $entity_id = $row->getDestinationProperty('entity_id');
    $bundle = $row->getDestinationProperty('bundle');

    // Save values and remove them from destination.
    $values = $row->getDestinationProperty('values');
    $row->removeDestinationProperty('values');

    if (!$values) {
      // Nothing is changing for this entity, exit here.
      return [$entity_type_id, $entity_id];
    }

    $bundle = $row->getDestinationProperty('bundle');
    $plugin_id = "entity:$entity_type_id";

    // Statically cache the entity keys.
    if (!isset($this->key[$entity_type_id])) {
      $entity_type = \Drupal::entityTypeManager()->getDefinition($entity_type_id);
      $this->key[$entity_type_id] = [
        'id' => $entity_type->getKey('id'),
        'bundle' => $entity_type->getKey('bundle'),
      ];
    }

    // Move the entity ID and bundle under their entity keys.
    $row->setDestinationProperty($this->key[$entity_type_id]['id'], $entity_id);
    $row->removeDestinationProperty('entity_id');
    $row->setDestinationProperty($this->key[$entity_type_id]['bundle'], $bundle);
    $row->removeDestinationProperty('bundle');

    $configuration = [
      'plugin' => $plugin_id,
      'default_bundle' => $bundle,
      'update_existing' => TRUE,
    ];

    foreach ($values as $field => $value) {
      $row->setDestinationProperty("$field/value", $value);
      $row->setDestinationProperty("$field/format", 'content_editor');
    }

    if (!isset($this->plugin[$plugin_id])) {
      $this->plugin[$plugin_id] = $this->destinationPluginManager->createInstance($plugin_id, $configuration, $this->migration);
    }

    // Run the entity type specific import.
    $ids = $this->plugin[$plugin_id]->import($row, $old_destination_id_values);

    // The destination ID has the pattern [{$entity_type_id}, {$entity_id}].
    return array_merge([$entity_type_id], $ids);
  }

}
