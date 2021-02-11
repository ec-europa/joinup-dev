<?php

declare(strict_types = 1);

namespace Drupal\joinup_stats_test\Mocks;

use Drupal\matomo_reporting_api\MatomoQueryFactory;

/**
 * Test replacement for MatomoQueryFactory.
 *
 * @see \Drupal\matomo_reporting_api\MatomoQueryFactory
 */
class TestMatomoQueryFactory extends MatomoQueryFactory {

  /**
   * {@inheritdoc}
   */
  public function createFactoryInstance(): TestQueryFactory {
    return new TestQueryFactory();
  }

}
