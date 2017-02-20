<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\Component\Utility\UrlHelper;
use Drupal\migrate\Row;

/**
 * Migrates collections.
 *
 * @MigrateSource(
 *   id = "collection"
 * )
 */
class Collection extends CollectionBase {

  use CountryTrait;

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return parent::fields() + [
      'uri' => $this->t('URI'),
      'policy2' => $this->t('Policy domain'),
      'abstract' => $this->t('Abstract'),
      'access_url' => $this->t('Access URL'),
      'created_time' => $this->t('Creation date'),
      'body' => $this->t('Description'),
      'elibrary' => $this->t('eLibrary creation'),
      'changed_time' => $this->t('Last changed date'),
      'owner' => $this->t('Owner'),
      'country' => $this->t('Spatial coverage'),
      'affiliates' => $this->t('Affiliates'),
      'contact' => $this->t('Contact info'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    return parent::query()->fields('c', [
      'nid',
      'vid',
      'type',
      'uri',
      'created_time',
      'changed_time',
      'abstract',
      'body',
      'policy2',
      'elibrary',
      'owner',
      'contact',
      'access_url',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $collection = $row->getSourceProperty('collection');

    if ($access_url = $row->getSourceProperty('access_url')) {
      if (!UrlHelper::isValid($access_url)) {
        // Don't import malformed URLs.
        $access_url = NULL;
      }
      elseif (parse_url($access_url, PHP_URL_SCHEME) === NULL) {
        // Needs a full-qualified URL.
        $access_url = "http://$access_url";
      }
      $row->setSourceProperty('access_url', $access_url);
    }

    // Get affiliates.
    $affiliates = $this->select('d8_solution', 's')
      ->fields('s', ['nid'])
      ->condition('s.collection', $collection)
      ->execute()
      ->fetchCol();
    $row->setSourceProperty('affiliates', $affiliates);

    // Log missed owner values.
    if (!$row->getSourceProperty('owner')) {
      $this->migration->getIdMap()->saveMessage(['collection' => $collection], "No owner for '$collection'");
    }

    drush_print_r($collection);
    drush_print_r($row->getSourceProperty('contact'));

    // Spatial coverage.
    $row->setSourceProperty('country', $this->getSpatialCoverage($row));

    return parent::prepareRow($row);
  }

  /**
   * Gets the spatial coverage for a collection.
   *
   * @param \Drupal\migrate\Row $row
   *   The source row.
   *
   * @return string[]
   *   A list of country names.
   */
  protected function getSpatialCoverage(Row $row) {
    // The country list is inherited from corresponding Drupal 6 node.
    if (in_array($row->getSourceProperty('type'), ['repository', 'community'])) {
      $vids = [$row->getSourceProperty('vid')];
    }
    // The country list is compiled from the compounding content-types.
    else {
      $query = $this->select('d8_mapping', 'm')
        ->distinct()
        ->fields('n', ['vid'])
        ->condition('m.collection', $row->getSourceProperty('collection'))
        ->condition('n.type', ['asset_release'], 'IN')
        ->condition('m.migrate', 1)
        ->isNotNull('m.nid');
      $query->join('node', 'n', 'm.nid = n.nid');
      $vids = $query->execute()->fetchCol();
    }

    return $vids ? $this->getCountries($vids) : [];
  }

}
