<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\Query\Condition;
use Drupal\joinup_migrate\Plugin\JoinupMigrateTrait;
use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;
use Drupal\migrate\Plugin\MigrateIdMapInterface;

/**
 * Migrates collections.
 *
 * @MigrateSource(
 *   id = "collection"
 * )
 */
class Collection extends SourcePluginBase  {

  use JoinupMigrateTrait;

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return 'Collections';
  }

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
    return ['collection' => $this->t('Collection name')];
  }

  /**
   * {@inheritdoc}
   */
  public function initializeIterator() {
    $query = clone $this->query();
    $query->addTag('migrate');
    $query->addTag('migrate_' . $this->migration->id());
    $query->addMetaData('migration', $this->migration);

    $alias = $query->leftJoin($this->migration->getIdMap()->getQualifiedMapTableName(), 'map', 'collection = map.sourceid1');
    $conditions = $query->orConditionGroup();
    $conditions->isNull("$alias.sourceid1");
    $conditions->condition("$alias.source_row_status", MigrateIdMapInterface::STATUS_NEEDS_UPDATE);

    $query->addField($alias, 'sourceid1', 'migrate_map_sourceid1');
    if ($n = count($this->migration->getDestinationIds())) {
      for ($count = 1; $count <= $n; $count++) {
        $map_key = 'destid' . $count++;
        $query->addField($alias, $map_key, "migrate_map_$map_key");
      }
    }
    $query->addField($alias, 'source_row_status', 'migrate_map_source_row_status');
    $query->condition($conditions);

    return new \IteratorIterator($query->execute());
  }

  /**
   * Returns the query that retrieves the data
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   */
  protected function query() {
    $query =  Database::getConnection()->select('joinup_migrate_mapping', 'j', ['fetch' => \PDO::FETCH_ASSOC]);
    $query->leftJoin("{$this->getSourceDatabaseName()}.node", 'n', 'j.nid = n.nid');

    $and = (new Condition('AND'))
      ->condition('j.new_collection', 'No')
      ->condition('n.type', ['community', 'repository'], 'IN');

    $or = (new Condition('OR'))
      ->condition('j.new_collection', 'Yes')
      ->condition($and);

    return $query
      ->fields('j', ['collection'])
      ->orderBy('j.collection')
      ->condition('j.del', 'No')
      ->condition('j.collection', ['', '#N/A'], 'NOT IN')
      ->condition($or);
  }

  /**
 * (
 *   j.del = :db_condition_placeholder_0
 * )
 * AND
 * (
 *   j.collection NOT IN (:db_condition_placeholder_1, :db_condition_placeholder_2)
 * )
 * AND
 * (
 *   (
 *     j.new_collection = :db_condition_placeholder_3
 *   )
 *   OR
 *   (
 *     (
 *       j.new_collection = :db_condition_placeholder_4
 *     )
 *     AND
 *     (
 *       n.type = :db_condition_placeholder_5:db_condition_placeholder_6
 *     )
 *   )
 * )
 * AND
 * (
 *   (
 *     map.sourceid1 IS NULL
 *   )
 *   OR
 *   (
 *     map.source_row_status = :db_condition_placeholder_7
 *   )
 * )
 *
 *
 */
}
