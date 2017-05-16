<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Migrates news.
 *
 * @MigrateSource(
 *   id = "news"
 * )
 */
class News extends NodeBase {

  use CountryTrait;
  use KeywordsTrait;
  use StateTrait;

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'source_url' => $this->t('Source URL'),
      'keywords' => $this->t('Keywords'),
      'country' => $this->t('Spatial coverage'),
      'state' => $this->t('State'),
    ] + parent::fields();
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('d8_news', 'n')->fields('n', [
      'source_url',
      'state',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $nid = $row->getSourceProperty('nid');
    $vid = $row->getSourceProperty('vid');

    // Keywords.
    $this->setKeywords($row, 'keywords', $nid, $vid);

    // Spatial coverage.
    $row->setSourceProperty('country', $this->getCountries([$vid]));

    // State.
    $this->setState($row);

    return parent::prepareRow($row);
  }

}
