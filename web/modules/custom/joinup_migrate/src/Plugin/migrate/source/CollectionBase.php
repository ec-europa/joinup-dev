<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\Query\Condition;
use Drupal\Core\State\StateInterface;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * BAse class for collection migrations.
 */
abstract class CollectionBase extends SqlBase {

  /**
   * Source database name.
   *
   * @var string
   */
  protected $dbName;

  /**
   * Collect here table aliases.
   *
   * @var string[]
   */
  protected $alias = [];

  /**
   * Constructs a collection migration.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, StateInterface $state) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $state);
    $this->dbName = $this->getDatabase()->getConnectionOptions()['database'];
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
  public function fields() {
    return [
      'collection' => $this->t('Collection name'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = Database::getConnection()->select('joinup_migrate_mapping', 'j', ['fetch' => \PDO::FETCH_ASSOC]);

    $this->alias['node'] = $query->leftJoin("{$this->dbName}.node", 'n', "j.nid = %alias.nid AND j.new_collection = 'No' AND %alias.type IN ('community', 'repository')");
    $this->alias['uri'] = $query->leftJoin("{$this->dbName}.content_field_id_uri", 'uri', "{$this->alias['node']}.vid = %alias.vid");
    $this->alias['repository'] = $query->leftJoin("{$this->dbName}.content_type_repository", 'repo', "{$this->alias['node']}.vid = %alias.vid");

    $or = (new Condition('OR'))
      ->condition((new Condition('AND'))
        ->condition('j.new_collection', 'Yes')
        ->isNotNull('j.policy')
        ->isNotNull('j.abstract')
      )
      ->condition("{$this->alias['node']}.type", ['community', 'repository'], 'IN');

    return $query
      ->fields('j', ['collection'])
      ->orderBy('j.collection')
      ->condition('j.del', 'No')
      ->condition('j.collection', ['', '#N/A'], 'NOT IN')
      ->condition($or);
  }

}
