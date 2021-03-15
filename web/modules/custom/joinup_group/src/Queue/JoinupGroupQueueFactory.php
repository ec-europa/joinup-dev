<?php

declare(strict_types = 1);

namespace Drupal\joinup_group\Queue;

use Drupal\Core\Queue\QueueDatabaseFactory;
use Drupal\Core\Queue\QueueInterface;

/**
 * Factory to produce JoinupGroupQueue queues.
 */
class JoinupGroupQueueFactory extends QueueDatabaseFactory {

  /**
   * {@inheritdoc}
   */
  public function get($name): QueueInterface {
    return new JoinupGroupQueue($name, $this->connection);
  }

}
