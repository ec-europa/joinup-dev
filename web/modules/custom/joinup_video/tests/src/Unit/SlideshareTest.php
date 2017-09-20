<?php

namespace Drupal\Tests\joinup_video\Unit;

use Drupal\joinup_video\Plugin\video_embed_field\Provider\Slideshare;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the GoogleDocs provider.
 *
 * @coversDefaultClass \Drupal\joinup_video\Plugin\video_embed_field\Provider\Slideshare
 *
 * @group joinup_video
 */
class SlideshareTest extends UnitTestCase {

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
    $actual_id = Slideshare::getIdFromInput($url);
    $this->assertEquals($expected_id, $actual_id);
  }

  /**
   * Provides test cases for ::testGetIdFromInput.
   */
  public function providerTestGetIdFromInput() {
    return [
      'standard url with https' => [
        'https://slideshare.net/slideshow/embed_code/key/sadjhflsdhf453984rf-asd',
        'sadjhflsdhf453984rf-asd',
      ],
      'standard url with http' => [
        'http://www.slideshare.net/slideshow/embed_code/key/sad-f0ak40_FEPdf-sdffjase',
        'sad-f0ak40_FEPdf-sdffjase',
      ],
      'url with query parameters' => [
        'http://www.slideshare.net/slideshow/embed_code/key/asj4908wjcw403jc?arg=true',
        'asj4908wjcw403jc',
      ],
      'url without protocol' => [
        'slideshare.net/slideshow/embed_code/key/f4j0pkwcpck049ik430#asdf',
        'f4j0pkwcpck049ik430',
      ],
      // Failing tests.
      'not including all required parameters' => [
        'https://slideshare.net/embed_code/sadfjafs49k4avc',
        FALSE,
      ],
    ];
  }

}
