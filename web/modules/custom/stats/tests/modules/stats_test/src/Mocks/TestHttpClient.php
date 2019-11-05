<?php

declare(strict_types = 1);

namespace Drupal\joinup_stats_test\Mocks;

/**
 * Test replacement for \Matomo\ReportingApi\HttpClient.
 *
 * @see \Matomo\ReportingApi\HttpClient
 */
class TestHttpClient {

  /**
   * Stub for the original method.
   *
   * @param string $method
   *   The method.
   */
  public function setMethod(string $method): void {}

}
