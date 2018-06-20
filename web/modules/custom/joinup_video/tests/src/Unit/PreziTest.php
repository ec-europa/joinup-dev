<?php

namespace Drupal\Tests\joinup_video\Unit;

use Drupal\joinup_video\Plugin\video_embed_field\Provider\Prezi;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the Prezi provider.
 *
 * @coversDefaultClass \Drupal\joinup_video\Plugin\video_embed_field\Provider\Prezi
 *
 * @group joinup_video
 */
class PreziTest extends UnitTestCase {

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
    $actual_id = Prezi::getIdFromInput($url);
    $this->assertEquals($expected_id, $actual_id);
  }

  /**
   * Provides test cases for ::testGetIdFromInput.
   */
  public function providerTestGetIdFromInput() {
    return [
      'standard url with https' => [
        'https://prezi.com/embed/asdf09d8sauf84',
        'asdf09d8sauf84',
      ],
      'standard url with http' => [
        'http://media-prezi.com/embed/asdfas_asdjf98sa44',
        'asdfas_asdjf98sa44',
      ],
      'url with query parameters' => [
        'http://media-prezi.com/embed/jsad908fj0a4898aj4s?arg=true',
        'jsad908fj0a4898aj4s',
      ],
      'url without protocol' => [
        'media-prezi.com/embed/3984j98foc0erkfsd?arg=true',
        '3984j98foc0erkfsd',
      ],
      // Failing tests.
      'not including embed' => [
        'http://media-prezi.com/3984j98foc0erkfsd?arg=true',
        FALSE,
      ],
      'including www.' => [
        'http://www.prezi.com/embed/kjf8943jhfo3j48i9jf',
        FALSE,
      ],
    ];
  }

}
