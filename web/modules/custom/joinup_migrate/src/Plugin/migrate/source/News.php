<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\Component\Utility\Unicode;
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
      'kicker' => $this->t('Kicker'),
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

    // Attachments.
    $fids = $this->select('content_field_documentation', 'a')
      ->fields('a', ['field_documentation_fid'])
      ->condition('a.vid', $row->getSourceProperty('vid'))
      ->execute()
      ->fetchCol();
    $row->setSourceProperty('fids', $fids);

    $kicker = trim($row->getSourceProperty('title'));
    if (Unicode::strlen($kicker) > 30) {
      $kicker = trim(Unicode::substr($kicker, 0, 29)) . '…';
    }
    $row->setSourceProperty('kicker', $kicker);

    // Source URL.
    if ($source_url = $row->getSourceProperty('source_url')) {
      if (strtolower($source_url) === 'n/a') {
        $source_url = NULL;
      }
      elseif (!preg_match('#^https?://#', $source_url)) {
        $source_url = "http://$source_url";
      }
      $row->setSourceProperty('source_url', $source_url);
    }

    return parent::prepareRow($row);
  }

}
