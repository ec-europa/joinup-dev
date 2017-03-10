<?php

namespace Drupal\joinup_migrate\Plugin\migrate\process;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a processor for mapping_table migration.
 *
 * @MigrateProcessPlugin(
 *   id = "mapping_nid"
 * )
 */
class MappingNid extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Connection to source database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $db;

  /**
   * Constructs a MappingNid object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $db
   *   Connection to source database.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $db) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->db = $db;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      Database::getConnection('default', 'migrate')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $row_number = $row->getSourceProperty('row_index');
    if (!is_numeric($value)) {
      throw new MigrateSkipRowException("Row #$row_number: Invalid Nid '$value'.", FALSE);
    }

    if (!$title = $this->db->select('node')->fields('node', ['title'])->condition('nid', $value)->execute()->fetchField()) {
      $migrate_executable->saveMessage("Node ID $value, row #$row_number doesn't exist in the source database.");
    }
    elseif ($row->getSourceProperty('Type of content item') === 'Interoperability Solution') {
      // Check for 'asset_release' acting as 'release'.
      /** @var \Drupal\Core\Database\Query\SelectInterface $query */
      $query = $this->db->select('og_ancestry', 'o')
        ->fields('o', ['nid'])
        ->condition('o.nid', (int) $value)
        ->condition('g.type', 'project_project');
      $query->join('node', 'g', 'o.group_nid = g.nid');
      // Is release.
      if ($query->execute()->fetchField()) {
        $migrate_executable->saveMessage("Interoperability solution '$title' ($value), acting as release, is in Excel file but it shouldn't. Releases are computed.");
      }
    }

    return $value;
  }

}
