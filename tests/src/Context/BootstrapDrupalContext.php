<?php

declare(strict_types = 1);

namespace Drupal\joinup\Context;

use Drupal\DrupalExtension\Context\RawDrupalContext;

/**
 * Behat context that takes care of bootstrapping Drupal.
 */
class BootstrapDrupalContext extends RawDrupalContext {

  /**
   * Bootstraps Drupal.
   *
   * We need to ensure that Drupal is properly bootstrapped before we run any
   * other hooks or execute step definitions. By calling `::getDriver()` we can
   * be sure that Drupal is ready to rock.
   *
   * This context file should be placed as the very first in the list.
   *
   * @BeforeScenario @api
   */
  public function bootstrap(): void {
    $driver = $this->getDriver();
    if (!$driver->isBootstrapped()) {
      $driver->bootstrap();
    }
  }

}
