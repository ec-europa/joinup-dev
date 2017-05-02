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
   * The migration used to lookup for file ID.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $fileMigration;

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
   * Gets the file URL field target ID.
   *
   * This method builds the target_id value of a file URL field. If the row file
   * property ($file_property) indicates an incoming uploaded file, the method
   * will lookup in the file migration ($file_migration_id), using the source
   * IDs provided in $file_source_ids, and will build a URI based on the
   * corresponding file. If the row contains no valid file source property, the
   * method will try to get a remote URI from the row $url_property property.
   *
   * @param \Drupal\migrate\Row $row
   *   The migrate row to be altered.
   * @param string $file_url_field_property
   *   The source property corresponding to the file URL field to be set.
   * @param array $file_source_ids
   *   The file migration source IDs values. Example ['nid' => 123].
   * @param string $file_property
   *   The source property that indicates if a uploaded file should be set.
   * @param string $file_migration_id
   *   The ID of the file migration to be used for file ID lookup.
   * @param string $url_property
   *   The source property of the remote URL to be set.
   */
  protected function setFileUrlTargetId(Row $row, $file_url_field_property, array $file_source_ids, $file_property, $file_migration_id, $url_property) {
    if ($row->getSourceProperty($file_property)) {
      $uri = NULL;
      // The target ID points to an uploaded file. Lookup in the file migration,
      // using the provided source IDs values, and get the migrated file ID.
      $lookup = $this->getFileMigration($file_migration_id)->getIdMap()
        ->lookupDestinationIds($file_source_ids);
      if (!empty($lookup[0][0])) {
        global $base_url;
        $uri = $base_url . '/file-dereference/' . $lookup[0][0];
      }
    }
    else {
      // The URI might be a reference to a remote file or NULL.
      $uri = $this->normalizeUri($row->getSourceProperty($url_property));
    }
    $row->setSourceProperty($file_url_field_property, $uri);
  }

  /**
   * Gets the file migration.
   *
   * @param string $file_migration_id
   *   The file migration ID.
   *
   * @return \Drupal\migrate\Plugin\MigrationInterface
   *   The 'documentation_file' migration.
   */
  protected function getFileMigration($file_migration_id) {
    if (!isset($this->fileMigration)) {
      $this->fileMigration = $this->migrationPluginManager->createInstance($file_migration_id);
    }
    return $this->fileMigration;
  }

}
