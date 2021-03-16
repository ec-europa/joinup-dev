<?php

declare(strict_types = 1);

namespace Drupal\joinup_group\Queue;

use Drupal\Core\Queue\DatabaseQueue;

/**
 * Extends the core's database queue.
 *
 * We need a way to delete queue items created on behalf of a certain group.
 * This queue class is very similar to parent DatabaseQueue, except that
 * provides an additional table column where the group ID hash is stored. A new
 * method, self::deleteGroupItems(), knows to delete the queue items created by
 * a specific group.
 *
 * @see \Drupal\joinup_group\JoinupGroupContentUrlAliasUpdater::queueGroupContent()
 */
class JoinupGroupQueue extends DatabaseQueue {

  /**
   * {@inheritdoc}
   */
  const TABLE_NAME = 'joinup_group_queue';

  /**
   * {@inheritdoc}
   */
  public function schemaDefinition(): array {
    $schema = parent::schemaDefinition();
    $schema['fields']['group_hash'] = [
      'type' => 'varchar_ascii',
      'length' => 32,
      'not null' => TRUE,
      'description' => 'The MD5 of the group ID',
    ];
    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  protected function doCreateItem($data) {
    // Almost the same code as parent, except that it extracts the group ID from
    // data and stores it in a separate table column, as MD5 hash.
    $group_hash = md5($data['group_id']);
    unset($data['group_id']);
    $query = $this->connection->insert(static::TABLE_NAME)
      ->fields([
        'name' => $this->name,
        'data' => serialize($data),
        // We cannot rely on REQUEST_TIME because many items might be created
        // by a single request which takes longer than 1 second.
        'created' => time(),
        'group_hash' => $group_hash,
      ]);
    // Return the new serial ID, or FALSE on failure.
    return $query->execute();
  }

  /**
   * Deletes queue items belonging to certain group.
   *
   * @param string $group_id
   *   The group ID.
   *
   * @throws \Exception
   *   If the operation cannot be completed.
   */
  public function deleteGroupItems(string $group_id): void {
    try {
      $this->connection->delete(static::TABLE_NAME)
        ->condition('name', $this->name)
        ->condition('group_hash', md5($group_id))
        ->execute();
    }
    catch (\Exception $e) {
      $this->catchException($e);
    }
  }

}
