<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_video\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\joinup_video\Plugin\video_embed_field\Provider\InternalPath;

/**
 * Tests the Internal path plugin.
 *
 * @coversDefaultClass \Drupal\joinup_video\Plugin\video_embed_field\Provider\InternalPath
 */
class InternalPathTest extends UnitTestCase {

  /**
   * Tests the structure of the URLs in the InternalPath plugin.
   *
   * @param string $url
   *   The input URL.
   * @param string $expected_id
   *   The expected ID or FALSE if it cannot be extracted from $url.
   * @param bool $has_iframe
   *   Whether it is expected to find an iframe parameter in the page.
   *
   * @covers ::getIdFromInput
   * @dataProvider providerTestGetIdFromInput
   */
  public function testGetIdFromInput(string $url, string $expected_id, bool $has_iframe): void {
    $data = InternalPath::getDataFromInput($url);
    $this->assertEquals($expected_id, $data['id']);
    $this->assertEquals(isset($data['iframe']), $has_iframe);
  }

  /**
   * Provides test cases for ::testGetIdFromInput.
   */
  public function providerTestGetIdFromInput(): array {
    return [
      'simple internal path' => [
        'http://example.com/test_1',
        'test_1',
        FALSE,
      ],
      'simple internal path with iframe' => [
        'http://example.com/test_1?iframe',
        'test_1',
        TRUE,
      ],
      'simple internal path with iframe ending in =' => [
        'http://example.com/test_1?iframe=',
        'test_1',
        TRUE,
      ],
      'simple internal path with iframe ending in character' => [
        'http://example.com/test_1?iframe=blah',
        'test_1',
        TRUE,
      ],
      'simple internal path with faulty iframe' => [
        'http://example.com/test_1?testiframe=',
        'test_1',
        FALSE,
      ],
      'simple internal path with iframe in different position' => [
        'http://example.com/test_1?foo=bar&iframe=&bar=foo',
        'test_1',
        TRUE,
      ],
      'simple internal path with faulty iframe in different position' => [
        'http://example.com/test_1?foo=bar&iframett&bar=foo',
        'test_1',
        FALSE,
      ],
    ];
  }

}
