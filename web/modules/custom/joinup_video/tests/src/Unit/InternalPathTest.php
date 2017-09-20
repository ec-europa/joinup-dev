<?php

namespace Drupal\Tests\joinup_video\Unit;

use Drupal\joinup_video\Plugin\video_embed_field\Provider\InternalPath;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the InternalPath provider.
 *
 * @coversDefaultClass \Drupal\joinup_video\Plugin\video_embed_field\Provider\InternalPath
 *
 * @group joinup_video
 */
class InternalPathTest extends UnitTestCase {

  /**
   * Tests the extraction of ID from input.
   *
   * @param string $url
   *   The input URL.
   * @param string|false $expected_id
   *   The expected ID or FALSE if it cannot be extracted from $url.
   *
   * @covers ::getIdFromInput
   * @dataProvider providerTestGetIdFromInput
   */
  public function testGetIdFromInput($url, $expected_id) {
    $actual_id = InternalPath::getIdFromInput($url);
    $this->assertEquals($expected_id, $actual_id);
  }

  /**
   * Provides test cases for ::testGetIdFromInput.
   */
  public function providerTestGetIdFromInput() {
    return [
      'standard url with https' => [
        'https://joinup.eu/homepage',
        'homepage',
      ],
      'standard url with http' => [
        'http://joinup.eu/someindex.php',
        'someindex.php',
      ],
      'url with query parameters' => [
        'http://joinup.eu/widget?arg=true',
        'widget',
      ],
      'url with multiple arguments' => [
        'http://joinup.eu/widget/test/something?arg=true',
        'widget/test/something',
      ],
      'url without protocol' => [
        'joinup.eu/who-cares-what-the-url-is',
        'who-cares-what-the-url-is',
      ],
    ];
  }

}
