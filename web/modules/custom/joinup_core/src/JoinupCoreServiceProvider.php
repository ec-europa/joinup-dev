<?php

declare(strict_types = 1);

namespace Drupal\joinup_core;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;

/**
 * Sets the monolog handler paths from DRUPAL_MONOLOG_PATHS env var.
 */
class JoinupCoreServiceProvider implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container): void {
    if (!empty($_SERVER['DRUPAL_MONOLOG_PATHS'])) {
      foreach (explode(';', $_SERVER['DRUPAL_MONOLOG_PATHS']) as $item) {
        $item = str_replace(' ', '', $item);
        if (strpos($item, ':') !== FALSE) {
          [$handler, $path] = explode(':', $item);
          if ($handler) {
            $container->setParameter("joinup_core.monolog.paths.{$handler}", $path);
          }
        }
      }
    }
  }

}
