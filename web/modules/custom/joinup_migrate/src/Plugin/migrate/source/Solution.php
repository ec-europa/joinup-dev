<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Migrates solutions.
 *
 * @MigrateSource(
 *   id = "solution"
 * )
 */
class Solution extends SolutionBase {

  use ContactTrait;
  use CountryTrait;
  use MappingTrait;
  use OwnerTrait;
  use UriTrait;

  /**
   * {@inheritdoc}
   */
  protected $reservedUriTables = ['collection'];

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'uri' => $this->t('URI'),
      'title' => $this->t('Title'),
      'created_time' => $this->t('Creation date'),
      'body' => $this->t('Description'),
      'changed_time' => $this->t('Last changed date'),
      'owner' => $this->t('Owners'),
      'keywords' => $this->t('Keywords'),
      'landing_page' => $this->t('Landing page'),
      'logo' => $this->t('Logo'),
      'metrics_page' => $this->t('Metrics page'),
      'policy2' => $this->t('Policy domain'),
      'related' => $this->t('Related solutions'),
      'country' => $this->t('Country'),
      'status' => $this->t('Status'),
      'contact' => $this->t('Contact info'),
      'distribution' => $this->t('Distribution'),
    ] + parent::fields();
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    return parent::query()
      ->fields('s', [
        'vid',
        'title',
        'uri',
        'created_time',
        'changed_time',
        'body',
        'sid',
        'policy2',
        'landing_page',
        'metrics_page',
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $nid = $row->getSourceProperty('nid');
    $vid = $row->getSourceProperty('vid');

    // Destroy self lookup URIs.
    $uri = $row->getSourceProperty('uri');
    if ($uri == "https://joinup.ec.europa.eu/node/$nid") {
      $row->setSourceProperty('uri', NULL);
    }
    else {
      $alias = $this->select('url_alias', 'a')
        ->fields('a', ['dst'])
        ->condition('a.src', "node/$nid")
        ->orderBy('a.pid', 'DESC')
        ->range(0, 1)
        ->execute()
        ->fetchField();
      if ($alias && ($uri === $alias)) {
        $row->setSourceProperty('uri', NULL);
      }
    }

    // Keywords.
    $query = $this->select('term_node', 'tn');
    $query->join('term_data', 'td', 'tn.tid = td.tid');
    $keywords = $query
      ->fields('td', ['name'])
      ->condition('tn.nid', $nid)
      ->condition('tn.vid', $vid)
      // The keywords vocabulary vid is 28.
      ->condition('td.vid', 28)
      ->execute()
      ->fetchCol();
    $row->setSourceProperty('keywords', array_unique($keywords));

    // Filter and fix landing and metrics pages.
    foreach (['landing', 'metrics'] as $name) {
      if ($page = $row->getSourceProperty($name . '_page')) {
        $this->normalizeUri($name, $row, FALSE);
      }
    }

    // Spatial coverage.
    $row->setSourceProperty('country', $this->getCountries([$vid]));

    // Owners.
    $row->setSourceProperty('owner', $this->getSolutionOwners($vid) ?: NULL);

    // Contacts.
    $row->setSourceProperty('contact', $this->getSolutionContacts($vid) ?: NULL);

    // Distributions.
    $query = $this->select('content_field_asset_distribution', 'd')
      ->fields('n', ['nid'])
      ->condition('d.vid', $vid);
    $query->join('node', 'n', 'd.field_asset_distribution_nid = n.nid');
    $distributions = $query->execute()->fetchCol();
    $row->setSourceProperty('distribution', $distributions);

    return parent::prepareRow($row);
  }

}
