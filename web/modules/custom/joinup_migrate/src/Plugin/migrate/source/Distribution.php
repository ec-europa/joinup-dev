<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\Core\State\StateInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManager;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a distribution migration source plugin.
 *
 * @MigrateSource(
 *   id = "distribution"
 * )
 */
class Distribution extends DistributionBase {

  use UriTrait;

  /**
   * The migration plugin manager service.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManager
   */
  protected $migrationPluginManager;

  /**
   * The 'distribution_file' migration.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $distributionFileMigration;

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
   * @param \Drupal\migrate\Plugin\MigrationPluginManager $migration_plugin_manager
   *   The migration plugin manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, StateInterface $state, MigrationPluginManager $migration_plugin_manager) {
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
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'uri' => $this->t('URI'),
      'title' => $this->t('Name'),
      'access_url' => $this->t('Access URL'),
      'created_time' => $this->t('Created time'),
      'body' => $this->t('Description'),
      'licence' => $this->t('Licence'),
      'changed_time' => $this->t('Changed time'),
      'technique' => $this->t('Representation technique'),
    ] + parent::fields();
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();

    $this->alias['node_revision'] = $query->join('node_revisions', 'node_revision', "{$this->alias['node']}.vid = %alias.vid");
    $query->addExpression("FROM_UNIXTIME({$this->alias['node']}.created, '%Y-%m-%dT%H:%i:%s')", 'created_time');
    $query->addExpression("FROM_UNIXTIME({$this->alias['node']}.changed, '%Y-%m-%dT%H:%i:%s')", 'changed_time');

    $this->alias['content_type_distribution'] = $query->leftJoin('content_type_distribution', 'content_type_distribution', "{$this->alias['node']}.vid = %alias.vid");
    $this->alias['content_field_distribution_access_url1'] = $query->leftJoin('content_field_distribution_access_url1', 'content_field_distribution_access_url1', "{$this->alias['node']}.vid = %alias.vid");

    $this->alias['content_field_distribution_licence'] = $query->leftJoin('content_field_distribution_licence', 'content_field_distribution_licence', "{$this->alias['node']}.vid = %alias.vid");
    $this->alias['node_licence'] = $query->leftJoin('node', 'node_licence', "{$this->alias['content_field_distribution_licence']}.field_distribution_licence_nid = %alias.nid AND %alias.type = 'licence'");

    $query->addExpression("{$this->alias['node_licence']}.nid", 'licence');

    return $query
      ->fields($this->alias['node'], ['title', 'vid'])
      ->fields($this->alias['node_revision'], ['body'])
      ->fields($this->alias['content_type_distribution'], ['field_distribution_access_url_fid'])
      ->fields($this->alias['content_field_distribution_access_url1'], ['field_distribution_access_url1_url'])
      // Assure the URI field.
      ->addTag('uri');
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $nid = $row->getSourceProperty('nid');
    $vid = $row->getSourceProperty('vid');

    // Normalize URI.
    $this->normalizeUri('uri', $row, FALSE);

    // Representation technique.
    $query = $this->select('term_node', 'tn');
    $query->join('term_data', 'td', 'tn.tid = td.tid');
    $representation_technique = $query
      ->fields('td', ['name'])
      ->condition('tn.nid', $nid)
      ->condition('tn.vid', $vid)
      // The representation technique vocabulary vid is 70.
      ->condition('td.vid', 70)
      ->execute()
      ->fetchCol();
    $row->setSourceProperty('technique', $representation_technique);

    // Resolve 'access_url'.
    $access_url = NULL;
    if ($fid = $row->getSourceProperty('field_distribution_access_url_fid')) {
      // The 'access_url' is a file, lookup in the 'distribution_file' migration
      // to get the migrated file ID.
      if ($lookup = $this->getDistributionFileMigration()->getIdMap()->lookupDestinationIds(['nid' => $nid])) {
        if (!empty($lookup[0][0])) {
          global $base_url;
          $access_url = $base_url . '/file-dereference/' . $lookup[0][0];
        }
      }
    }
    elseif ($url = $row->getSourceProperty('field_distribution_access_url1_url')) {
      // The 'access_url' is reference to a remote file.
      $access_url = $url;
    }
    $row->setSourceProperty('access_url', $access_url);

    return parent::prepareRow($row);
  }

  /**
   * Gets 'distribution_file' migration.
   *
   * @return \Drupal\migrate\Plugin\MigrationInterface
   *   The 'distribution_file' migration.
   */
  protected function getDistributionFileMigration() {
    if (!isset($this->distributionFileMigration)) {
      $this->distributionFileMigration = $this->migrationPluginManager->createInstance('distribution_file');
    }
    return $this->distributionFileMigration;
  }

}
