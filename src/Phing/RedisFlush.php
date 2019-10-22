<?php

declare(strict_types = 1);

namespace DrupalProject\Phing;

use Drupal\redis\ClientFactory;

/**
 * Phing task to flush Redis.
 */
class RedisFlush extends \Task {

  /**
   * {@inheritdoc}
   */
  public function main(): void {
    try {
      ClientFactory::getClient()->flushAll();
      $this->log('Redis cache flushed.');
    }
    catch (\Exception $e) {
      $this->log('Error flushing redis cache: ' . $e->getMessage(),\Project::MSG_ERR);
    }

  }

}
