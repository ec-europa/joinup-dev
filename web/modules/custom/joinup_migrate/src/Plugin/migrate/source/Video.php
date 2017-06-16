<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Migrates videos.
 *
 * @MigrateSource(
 *   id = "video"
 * )
 */
class Video extends NodeBase {

  use CountryTrait;
  use KeywordsTrait;

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'collection' => $this->t('Collection'),
      'video' => $this->t('Video URI'),
      'keywords' => $this->t('Keywords'),
      'country' => $this->t('Spatial coverage'),
    ] + parent::fields();
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('d8_video', 'n')->fields('n', ['video']);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $nid = $row->getSourceProperty('nid');
    $vid = $row->getSourceProperty('vid');

    // Keywords.
    $this->setKeywords($row, 'keywords', $nid, $vid, [28, 27]);

    // Spatial coverage.
    $row->setSourceProperty('country', $this->getCountries([$vid]));

    return parent::prepareRow($row);
  }

}
