<?php

namespace Drupal\Tests\joinup_video\Unit;

use Drupal\joinup_video\Plugin\video_embed_field\Provider\GoogleDocs;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the GoogleDocs provider.
 *
 * @coversDefaultClass \Drupal\joinup_video\Plugin\video_embed_field\Provider\GoogleDocs
 *
 * @group joinup_video
 */
class GoogleDocsTest extends UnitTestCase {

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
    $actual_id = GoogleDocs::getIdFromInput($url);
    $this->assertEquals($expected_id, $actual_id);
  }

  /**
   * Provides test cases for ::testGetIdFromInput.
   */
  public function providerTestGetIdFromInput() {
    return [
      'standard url with https' => [
        'https://docs.google.com/spreadsheets/d/imf0394jtoif9345jtf034pkf34f3094kf/pubhtml?widget=true&headers=false',
        'imf0394jtoif9345jtf034pkf34f3094kf',
      ],
      'standard url with http' => [
        'http://docs.google.com/spreadsheets/d/e/gdsafiluhsadlfuha_afsoiu4h484fhasdf',
        'gdsafiluhsadlfuha_afsoiu4h484fhasdf',
      ],
      'standard document' => [
        'https://docs.google.com/document/d/oiajs9fowjef_fajs9084htjf9348jhf/pubhtml?widget=true&headers=false',
        'oiajs9fowjef_fajs9084htjf9348jhf',
      ],
    ];
  }

}
