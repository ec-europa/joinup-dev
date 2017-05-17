<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

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
  protected $uriProperties = ['uri', 'access_url'];

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
      'owner_text' => $this->t('Text owner'),
      'country' => $this->t('Spatial coverage'),
      'affiliates' => $this->t('Affiliates'),
      'contact' => $this->t('Contact info'),
      'contact_email' => $this->t('Contact E-mail'),
      'state' => $this->t('Workflow state'),
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
      'owner_text',
      'contact',
      'contact_email',
      'access_url',
      'state',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $collection = $row->getSourceProperty('collection');

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
