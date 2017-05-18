<?php

namespace Drupal\joinup_migrate\Plugin\migrate\destination;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\migrate\destination\DestinationBase;
use Drupal\migrate\Plugin\MigrateDestinationFastRollbackInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a destination plugin for the collection_prepare migration.
 *
 * @MigrateDestination(
 *   id = "prepare"
 * )
 */
class Prepare extends DestinationBase implements MigrateDestinationFastRollbackInterface, ContainerFactoryPluginInterface {

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
      Database::getConnection('default', 'migrate')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'collection' => ['type' => 'string'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields(MigrationInterface $migration = NULL) {
    return [
      'collection' => $this->t('Collection'),
      'type' => $this->t('Node type'),
      'nid' => $this->t('Node ID'),
      'policy' => $this->t('Level1 policy domain'),
      'policy2' => $this->t('Level2 policy domain'),
      'abstract' => $this->t('Abstract'),
      'logo' => $this->t('Logo'),
      'banner' => $this->t('Banner'),
      'elibrary' => $this->t('Elibrary creation'),
      'publisher' => $this->t('Publisher'),
      'contact' => $this->t('Contact'),
      'collection_owner' => $this->t('Collection owner'),
      'state' => $this->t('State'),
      'roles' => $this->t('Roles'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    if (!$row->changed()) {
      return $old_destination_id_values;
    }

    $values = $row->getDestination();

    // Assure sane defaults for $values.
    foreach ($this->fields() as $key => $value) {
      if (!array_key_exists($key, $values)) {
        $values[$key] = NULL;
      }
    }

    $collection = $values['collection'];
    $insert = empty($old_destination_id_values);

    if ($insert) {
      // There's no primary key in the destination table. We need to manually
      // check the uniqueness.
      $found = (bool) $this->database->select('d8_prepare', 'c')
        ->fields('c', ['collection'])
        ->condition('c.collection', $collection)
        ->execute()
        ->fetchField();
      if ($found) {
        throw new MigrateException("Collection '$collection' already exist and cannot be inserted.");
      }
    }

    try {
      // Inserting.
      if ($insert) {
        $this->database->insert('d8_prepare')
          ->fields(array_keys($this->fields()))
          ->values($values)
          ->execute();
      }
      // Updating.
      else {
        unset($values['collection']);
        $this->database->update('d8_prepare')
          ->fields($values)
          ->condition('collection', $collection)
          ->execute();
      }
      return [$collection];
    }
    catch (MigrateException $exception) {
      throw new MigrateException($exception->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function rollback(array $destination_identifier) {
    parent::rollback($destination_identifier);
    $this->database->delete('d8_prepare')
      ->condition('collection', $destination_identifier['collection'])
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function rollbackMultiple(array $destination_ids) {
    $collections = array_map(function (array $item) {
      return $item['collection'];
    }, $destination_ids);
    $this->database->delete('d8_prepare')
      ->condition('collection', $collections, 'IN')
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function rollbackAll() {
    $this->database->truncate('d8_prepare')->execute();
  }

}
