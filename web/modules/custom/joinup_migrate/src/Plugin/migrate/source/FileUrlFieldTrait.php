<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\Core\State\StateInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Computes the file URL field target_id given a migrated file or remote URI.
 */
trait FileUrlFieldTrait {

  /**
   * The migration plugin manager service.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationPluginManager;

  /**
   * A list of migrations used to lookup for file ID.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface[]
   */
  protected $fileMigration = [];

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The current migration.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migration_plugin_manager
   *   The migration plugin manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, StateInterface $state, MigrationPluginManagerInterface $migration_plugin_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $state);
    $this->migrationPluginManager = $migration_plugin_manager;
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
      $container->get('state'),
      $container->get('plugin.manager.migration')
    );
  }

  /**
   * Gets the file URL field target IDs.
   *
   * Builds a list of the target_id values of a file URL field. If source ID
   * values are passed, the method performs a lookup, for each file, in the file
   * migration ($file_migration_id) map table, by iterating over the source ID
   * values provided in $file_source_ids. Depending of the destination field
   * cardinality, the method will try to get a remote URI from the row
   * $url_property property and append it to the file URL field items.
   *
   * @param \Drupal\migrate\Row $row
   *   The migrate row to be altered.
   * @param string $file_url_field_property
   *   The source property corresponding to the file URL field to be set.
   * @param array[] $file_source_id_values
   *   A list of source ID values corresponding to all the files attached to
   *   this entity. Example [['fid' => 123], ['fid' => 789]].
   * @param string $file_migration_id
   *   The ID of the file migration to be used for file ID lookup.
   * @param string $url_property
   *   The source property of the remote URL to be set.
   * @param int $mode
   *   (optional) Indicates how file uploads and remote URL are composing the
   *   file URL field. Possible values JoinupSqlBase::FILE_URL_MODE_*. Defaults
   *   to JoinupSqlBase::FILE_URL_MODE_SINGLE.
   */
  protected function setFileUrlTargetId(Row &$row, $file_url_field_property, array $file_source_id_values, $file_migration_id, $url_property, $mode = JoinupSqlBase::FILE_URL_MODE_SINGLE) {
    $items = [];
    if ($file_source_id_values) {
      global $base_url;
      $file_migration = $this->getFileMigration($file_migration_id);
      foreach ($file_source_id_values as $id) {
        // Lookup in the file migration, using the provided source IDs value,
        // and get the migrated file ID.
        $lookup = $file_migration->getIdMap()->lookupDestinationIds($id);
        if (!empty($lookup[0][0])) {
          $items[] = $base_url . '/file-dereference/' . $lookup[0][0];
        }
        // Break here if the cardinality is 1 and we already have one item.
        if (($mode === JoinupSqlBase::FILE_URL_MODE_SINGLE) && (count($items) === 1)) {
          break;
        }
      }
    }

    // Only add the remote URL if it's a multiple cardinality field or no file
    // reference has been added in the previous step.
    if (($mode === JoinupSqlBase::FILE_URL_MODE_MULTIPLE) || empty($items)) {
      // The URI might be a reference to a remote file or NULL.
      if ($url = $this->normalizeUri($row->getSourceProperty($url_property))) {
        $items[] = $url;
      }
    }

    // Store file URL items in the row under passed property.
    $row->setSourceProperty($file_url_field_property, $items);
  }

  /**
   * Gets the file migration.
   *
   * @param string $file_migration_id
   *   The file migration ID.
   *
   * @return \Drupal\migrate\Plugin\MigrationInterface
   *   The file migration.
   */
  protected function getFileMigration($file_migration_id) {
    if (!isset($this->fileMigration[$file_migration_id])) {
      $this->fileMigration[$file_migration_id] = $this->migrationPluginManager->createInstance($file_migration_id);
    }
    return $this->fileMigration[$file_migration_id];
  }

}
