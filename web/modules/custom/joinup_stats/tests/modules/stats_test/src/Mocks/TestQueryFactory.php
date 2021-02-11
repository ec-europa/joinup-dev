<?php

declare(strict_types = 1);

namespace Drupal\joinup_stats_test\Mocks;

/**
 * Test replacement for \Matomo\ReportingApi\QueryFactory.
 *
 * @see \Matomo\ReportingApi\QueryFactory
 */
class TestQueryFactory {

  /**
   * Stub for the original method.
   *
   * @return \Drupal\joinup_stats_test\Mocks\TestHttpClient
   *   The HTTP client.
   */
  public function getHttpClient(): TestHttpClient {
    return new TestHttpClient();
  }

  /**
   * Stub for the original method.
   *
   * @return \Drupal\joinup_stats_test\Mocks\TestQuery
   *   The query.
   */
  public function getQuery(): TestQuery {
    return new TestQuery();
  }

}
