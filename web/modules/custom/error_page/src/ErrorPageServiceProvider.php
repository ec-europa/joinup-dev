<?php

namespace Drupal\error_page;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\error_page\EventSubscriber\ErrorPageExceptionLoggingSubscriber;

/**
 * Swaps the core 'exception.logger' service's class.
 */
class ErrorPageServiceProvider implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $container->getDefinition('exception.logger')
      ->setClass(ErrorPageExceptionLoggingSubscriber::class);
  }

}
