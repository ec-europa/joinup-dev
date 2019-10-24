<?php

declare(strict_types = 1);

namespace DrupalProject\Phing;

use Drupal\Core\Site\Settings;
use Drupal\redis\ClientFactory;
use DrupalFinder\DrupalFinder;

/**
 * Phing task to flush Redis.
 */
class RedisFlush extends \Task {

  /**
   * {@inheritdoc}
   */
  public function main(): void {
    // Find Drupal root directory.
    $drupalFinder = new DrupalFinder();
    if (!$drupalFinder->locateRoot(getcwd())) {
      throw new \Exception('Cannot locate Drupal path.');
    }
    $drupalRootDir = $drupalFinder->getDrupalRoot();

    try {
      $settings = [];
      // Include the settings.php file just to get the Redis connection
      // configuration. We're only interested in `$settings['redis.connection']`
      // values but we need also to bootstrap Drupal in order to avoid any error
      // caused by constants, in settings.php.
      include "{$drupalRootDir}/core/includes/bootstrap.inc";
      include "{$drupalRootDir}/sites/default/settings.php";
      // Set the settings singleton so that ClientFactory::getClient() knows to
      // configure the Redis connection.
      // @see \Drupal\redis\ClientFactory::getClient()
      new Settings($settings);
      ClientFactory::getClient()->flushAll();
      $this->log('Redis cache flushed.');
    }
    catch (\Exception $e) {
      $this->log('Error flushing Redis cache: ' . $e->getMessage(), \Project::MSG_WARN);
    }
  }

}
