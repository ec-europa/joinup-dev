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
  use ElibraryCreationTrait;
  use MappingTrait;
  use OwnerTrait;
  use UriTrait;

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
      'elibrary' => $this->t('Elibrary creation'),
    ] + parent::fields();
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();

    $this->alias['content_type_asset_release'] = $query->leftJoin('content_type_asset_release', 'content_type_asset_release', "{$this->alias['node']}.vid = %alias.vid");
    $this->alias['node_documentation'] = $query->leftJoin('node', 'node_documentation', "{$this->alias['content_type_asset_release']}.field_asset_homepage_doc_nid = %alias.nid");
    $this->alias['content_type_documentation'] = $query->leftJoin('content_type_documentation', 'content_type_documentation', "{$this->alias['node_documentation']}.vid = %alias.vid");
    $this->alias['state'] = $query->leftJoin('workflow_node', 'state', "{$this->alias['node']}.nid = %alias.nid");

    $this->alias['content_field_asset_sw_metrics'] = $query->leftJoin('content_field_asset_sw_metrics', 'content_field_asset_sw_metrics', "{$this->alias['node']}.vid = %alias.vid");
    $this->alias['node_metrics'] = $query->leftJoin('node', 'node_metrics', "{$this->alias['content_field_asset_sw_metrics']}.field_asset_sw_metrics_nid = %alias.nid");
    $this->alias['data_set_uri'] = $query->leftJoin('content_field_id_uri', 'data_set_uri', "{$this->alias['node_metrics']}.vid = %alias.vid");

    $query->addExpression("TRIM({$this->alias['content_type_documentation']}.field_documentation_access_url1_url)", 'landing_page');
    $query->addExpression("FROM_UNIXTIME({$this->alias['node']}.created, '%Y-%m-%dT%H:%i:%s')", 'created_time');
    $query->addExpression("FROM_UNIXTIME({$this->alias['node']}.changed, '%Y-%m-%dT%H:%i:%s')", 'changed_time');
    $query->addExpression("TRIM({$this->alias['data_set_uri']}.field_id_uri_value)", 'metrics_page');

    return $query
      ->fields('m', ['elibrary', 'policy2'])
      ->fields($this->alias['node'], ['title', 'created', 'changed', 'vid'])
      ->fields($this->alias['node_revision'], ['body'])
      ->fields($this->alias['state'], ['sid'])
      // Assure the URI field.
      ->addTag('uri');
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

    // Assure a created date.
    if (!$row->getSourceProperty('created_time')) {
      $row->setSourceProperty('created_time', date('Y-m-d\TH:i:s', REQUEST_TIME));
    }
    // Assure a changed date.
    if (!$row->getSourceProperty('changed_time')) {
      $row->setSourceProperty('changed_time', date('Y-m-d\TH:i:s', REQUEST_TIME));
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

    // Elibrary creation.
    $this->elibraryCreation($row);

    return parent::prepareRow($row);
  }

}
