<?php

declare(strict_types = 1);

namespace Drupal\joinup_stats_test;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\joinup_stats_test\Mocks\TestMatomoQueryFactory;

/**
 * Swaps the 'matomo.query_factory' service class.
 */
class JoinupStatsTestServiceProvider implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container): void {
    $definition = $container->getDefinition('matomo.query_factory');
    $definition->setClass(TestMatomoQueryFactory::class);
  }

}
