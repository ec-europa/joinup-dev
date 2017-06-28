<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\joinup_migrate\RedirectImportInterface;
use Drupal\migrate\Row;

/**
 * Migrates collections.
 *
 * @MigrateSource(
 *   id = "collection"
 * )
 */
class Collection extends JoinupSqlBase implements RedirectImportInterface {

  use CountryTrait;
  use DefaultRdfRedirectTrait {
    getRedirectSources as rdfGetRedirectSources;
  }

  /**
   * {@inheritdoc}
   */
  protected $uriProperties = ['uri', 'access_url'];

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'collection' => [
        'type' => 'string',
        'alias' => 'c',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'collection' => $this->t('Collection'),
      'uri' => $this->t('URI'),
      'policy2' => $this->t('Policy domain'),
      'abstract' => $this->t('Abstract'),
      'access_url' => $this->t('Access URL'),
      'created_time' => $this->t('Creation date'),
      'body' => $this->t('Description'),
      'elibrary' => $this->t('eLibrary creation'),
      'changed_time' => $this->t('Last changed date'),
      'owner' => $this->t('Owner'),
      'owner_text_name' => $this->t('Text owner name'),
      'owner_text_type' => $this->t('Text owner type'),
      'country' => $this->t('Spatial coverage'),
      'affiliates' => $this->t('Affiliates'),
      'contact' => $this->t('Contact info'),
      'contact_email' => $this->t('Contact E-mail'),
      'state' => $this->t('Workflow state'),
      'banner' => $this->t('Banner'),
      'logo_id' => $this->t('Logo ID'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('d8_collection', 'c')->fields('c', [
      'collection',
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
      'owner_text_name',
      'owner_text_type',
      'contact',
      'contact_email',
      'access_url',
      'state',
      'banner',
      'logo_id',
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

    // Log inconsistencies.
    if (!$row->getSourceProperty('abstract')) {
      $this->migration->getIdMap()->saveMessage($row->getSourceIdValues(), "Collection '$collection' is missing an Abstract");
    }
    if (!$row->getSourceProperty('body')) {
      $this->migration->getIdMap()->saveMessage($row->getSourceIdValues(), "Collection '$collection' is missing a Description");
    }

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
        ->isNotNull('m.nid');
      $query->join('node', 'n', 'm.nid = n.nid');
      $vids = $query->execute()->fetchCol();
    }

    return $vids ? $this->getCountries($vids) : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getRedirectSources(Row $row) {
    // We collect the aliases from all collection components, as 'community' or
    // 'repository', omitting 'project_project' and 'asset_release' because
    // these are creating more specific redirects for solutions.
    $nids = $this->select('d8_mapping', 'm')
      ->fields('m', ['nid'])
      ->condition('m.collection', $row->getSourceProperty('collection'))
      ->condition('m.type', ['community', 'repository'], 'IN')
      ->execute()
      ->fetchCol();

    $sources = [];
    foreach ($nids as $nid) {
      // Mock a row, just to reuse the parent method.
      $fake_row = new Row(['nid' => $nid], ['nid' => $nid]);
      $sources = array_merge($sources, $this->rdfGetRedirectSources($fake_row));
    }

    return $sources;
  }

}
