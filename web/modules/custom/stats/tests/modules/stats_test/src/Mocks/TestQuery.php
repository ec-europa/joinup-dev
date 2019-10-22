<?php

declare(strict_types = 1);

namespace Drupal\joinup_stats_test\Mocks;

use Drupal\Component\Serialization\Json;
use GuzzleHttp\Psr7\Response;
use Matomo\ReportingApi\Query;
use Matomo\ReportingApi\QueryResult;

/**
 * Test replacement for \Matomo\ReportingApi\Query.
 *
 * @see \Matomo\ReportingApi\Query
 */
class TestQuery extends Query {

  /**
   * TestQuery constructor.
   */
  public function __construct() {}

  /**
   * {@inheritdoc}
   */
  public function execute(): QueryResult {
    return new QueryResult(new Response(200, [
      'Content-Type' => 'application/json',
    ], Json::encode($this->getMockedResponseArray())));
  }

  /**
   * Returns a mocked array response.
   *
   * @return array
   *   Mocked array response.
   */
  protected function getMockedResponseArray(): array {
    $increment = \Drupal::state()->get('joinup_stats_test.increment', []);
    $default = [
      'http://example.com/distro/1' => 55,
      'http://example.com/distro/2' => 2034,
      'http://example.com/distro/3' => 0,
      '1' => 3846545,
      '2' => 234,
      '3' => 8766,
      '4' => 334,
    ];
    $values = [];
    foreach ($default as $id => $count) {
      $value = $count;
      if (isset($increment[$id])) {
        $value += $increment[$id];
      }
      $key = is_numeric($id) ? 'nb_visits' : 'nb_hits';

      $values[] = [[$key => $value]];
    }

    return $values;
  }

}
