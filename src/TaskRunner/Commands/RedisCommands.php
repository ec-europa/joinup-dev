<?php

declare(strict_types = 1);

namespace Joinup\TaskRunner\Commands;

use OpenEuropa\TaskRunner\Commands\AbstractCommands;
use Predis\Client;

/**
 * Provides commands for Redis cache backend.
 */
class RedisCommands extends AbstractCommands {

  /**
   * Flushes the Redis backend.
   *
   * @command redis:flush
   */
  public function flush(): void {
    $parameters = array_filter([
      'host' => getenv('REDIS_HOST'),
      'port' => getenv('REDIS_PORT'),
      'password' => getenv('REDIS_PASSWORD'),
    ]);
    (new Client($parameters))->flushall();
    $this->say('Redis backend flushed');
  }

}
