<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

/**
 * Reusable code to store the documentation field.
 */
trait DocumentationTrait {

  /**
   * Gets the (D6) 'asset_release' documentation given its node revision ID.
   *
   * @param int $vid
   *   The (D6) 'asset_release' node revision ID.
   *
   * @return array[]
   *   An indexed array where the first item is a list of file IDs, each one
   *   represented as source IDs (example [['fid' => 123, 'fid' => 987]]) and
   *   the second item is a simple array of URLs.
   */
  protected function getAssetReleaseDocumentation($vid) {
    $items = $this->select('d8_file_documentation', 'd')->fields('d')
      ->condition('d.vid', $vid)
      ->execute()
      ->fetchAll();

    $return = [[], []];
    foreach ($items as $item) {
      if (!empty($item['fid'])) {
        $return[0][] = ['fid' => $item['fid']];
      }
      if (!empty($item['url'])) {
        $return[1][] = $item['url'];
      }
    }

    return $return;
  }

}
