<?php

namespace Drupal\joinup_migrate\Plugin\migrate\destination;

use Drupal\Core\Database\Connection;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Plugin\migrate\destination\DestinationBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a destination plugin for the Joinup mapping table.
 *
 * @MigrateDestination(
 *   id = "mapping_table"
 * )
 */
class MappingTable extends DestinationBase implements ContainerFactoryPluginInterface {

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a mapping_table destination plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration.
   * @param \Drupal\Core\Database\Connection $database
   *   The database service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, Connection $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    $this->database = $database;
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
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'id' => [
        'type' => 'integer',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields(MigrationInterface $migration = NULL) {
    return [
      'id' => $this->t('ID'),
      'type' => $this->t('Source node-type'),
      'nid' => $this->t('Source node ID'),
      'collection' => $this->t('Collection'),
      'policy' => $this->t('Policy domain'),
      'new_collection' => $this->t('Is new collection?'),
      'del' => $this->t('Delete?'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    $fields = $this->fields();
    unset($fields['id']);
    $fields = array_keys($fields);

    $id = $this->database->insert('joinup_migrate_mapping')
      ->fields($fields)
      ->values($row->getDestination())
      ->execute();

    return [$id];
  }

  /**
   * {@inheritdoc}
   */
  public function rollback(array $destination_identifier) {
    parent::rollback($destination_identifier);
    $this->database->delete('joinup_migrate_mapping')
      ->condition('id', $destination_identifier['id'])
      ->execute();
  }

}
