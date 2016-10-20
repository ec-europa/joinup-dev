<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\Query\Condition;
use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * Migrates collections.
 *
 * @MigrateSource(
 *   id = "collection"
 * )
 */
class Collection extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return ['collection' => ['type' => 'string']];
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'rid' => $this->t('RDF type ID'),
      'id' => $this->t('ID'),
      'collection' => $this->t('Collection name'),
      'new_collection' => $this->t('New collection?'),
      'policy' => $this->t('Policy domain'),
      'field_ar_state' => $this->t('State'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $source_db = $this->getSourceDatabaseName();

    $query = Database::getConnection()->select('joinup_migrate_mapping', 'j', ['fetch' => \PDO::FETCH_ASSOC]);

    $node_alias = $query->leftJoin("$source_db.node", 'n', "j.nid = %alias.nid AND j.new_collection = 'No' AND %alias.type IN ('community', 'repository')");
    $uri_alias = $query->leftJoin("$source_db.content_field_id_uri", 'uri', "$node_alias.vid = %alias.vid");

    $or = (new Condition('OR'))
      ->condition((new Condition('AND'))
        ->condition('j.new_collection', 'Yes')
        ->isNotNull('j.policy')
        ->isNotNull('j.abstract')
      )
      ->condition("$node_alias.type", ['community', 'repository'], 'IN');

    $query
      ->fields('j', ['collection', 'new_collection', 'policy'])
      ->fields($node_alias, ['nid', 'type'])
      ->orderBy('j.collection')
      ->condition('j.del', 'No')
      ->condition('j.collection', ['', '#N/A'], 'NOT IN')
      ->condition($or);

    $query->addExpression("$uri_alias.field_id_uri_value", 'uri');

    return $query;
  }

  /**
   * Gets the migration connection database name.
   *
   * @return string
   */
  protected function getSourceDatabaseName() {
    return $this->getDatabase()->getConnectionOptions()['database'];
  }

}
