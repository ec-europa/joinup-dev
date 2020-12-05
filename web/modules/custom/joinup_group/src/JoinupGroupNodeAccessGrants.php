<?php

declare(strict_types = 1);

namespace Drupal\joinup_group;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\joinup_group\Entity\GroupInterface;

/**
 * Provides node access grant IDs for groups.
 *
 * Group facilitators have the 'view any unpublished content' permission within
 * their groups. In order to make this work we provide a node access grant for
 * the 'joinup_group_view_unpublished' realm.
 *
 * The node access grants work with numeric grant IDs while our groups have
 * string IDs so we store a mapping in the 'joinup_group_node_access' table.
 * This service provides the mapping data.
 */
class JoinupGroupNodeAccessGrants implements JoinupGroupNodeAccessGrantsInterface {

  /**
   * The SQL connection class for the primary database storage.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $sqlConnection;

  /**
   * The static cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $staticCache;

  /**
   * Constructs a JoinupGroupNodeAccessGrants service.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The SQL connection class for the primary database storage.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The static cache backend.
   */
  public function __construct(Connection $connection, CacheBackendInterface $cache) {
    $this->sqlConnection = $connection;
    $this->staticCache = $cache;
  }

  /**
   * {@inheritdoc}
   */
  public function getNodeAccessGrantId(GroupInterface $group): int {
    if ($group->isNew()) {
      throw new \InvalidArgumentException('Cannot provide an access grant ID for an unsaved group.');
    }

    $cid = [
      __METHOD__,
      $group->getEntityTypeId(),
      $group->id(),
    ];
    $cid = implode(':', $cid);

    // Use cached result if it exists.
    if (!$gid = $this->staticCache->get($cid)->data ?? NULL) {
      $query = $this->sqlConnection->select('joinup_group_node_access', 'na')
        ->fields('na', ['gid'])
        ->condition('entity_type', $group->getEntityTypeId())
        ->condition('entity_id', $group->id())
        ->range(0, 1);

      $record = $query->execute()->fetch();
      $gid = empty($record) ? $this->createRecord($group) : (int) $record->gid;
      $this->staticCache->set($cid, $gid, Cache::PERMANENT, $group->getCacheTagsToInvalidate());
    }

    return $gid;
  }

  /**
   * Creates a new record in the access grants table for the given group.
   *
   * @param \Drupal\joinup_group\Entity\GroupInterface $group
   *   The group for which to create a record.
   *
   * @return int
   *   The grant ID.
   *
   * @throws \Exception
   *   Thrown when an error occurs during the creation of the database record.
   */
  protected function createRecord(GroupInterface $group): int {
    if ($group->isNew()) {
      throw new \InvalidArgumentException('Cannot create an access grant record for an unsaved group.');
    }

    $query = $this->sqlConnection->insert('joinup_group_node_access')
      ->fields(
        ['entity_type', 'entity_id'],
        [$group->getEntityTypeId(), $group->id()]
    );
    return (int) $query->execute();
  }

}
