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

  use CountryTrait;
  use UriTrait;

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'uri' => $this->t('URI'),
      'title' => $this->t('Title'),
      'created' => $this->t('Creation date'),
      'body' => $this->t('Description'),
      'changed' => $this->t('Last changed date'),
      'keywords' => $this->t('Keywords'),
      'landing_page' => $this->t('Landing page'),
      'logo' => $this->t('Logo'),
      'metrics_page' => $this->t('Metrics page'),
      'policy' => $this->t('Policy domain'),
      'related' => $this->t('Related solutions'),
      'country' => $this->t('Country'),
      'status' => $this->t('Status'),
    ] + parent::fields();
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();

    $this->alias['uri'] = $query->leftJoin("{$this->getSourceDbName()}.content_field_id_uri", 'uri', "{$this->alias['node']}.vid = %alias.vid");
    $this->alias['content_type_asset_release'] = $query->leftJoin("{$this->getSourceDbName()}.content_type_asset_release", 'content_type_asset_release', "{$this->alias['node']}.vid = %alias.vid");
    $this->alias['node_documentation'] = $query->leftJoin("{$this->getSourceDbName()}.content_type_documentation", 'node_documentation', "{$this->alias['content_type_asset_release']}.field_asset_homepage_doc_nid = %alias.nid");
    $this->alias['content_type_documentation'] = $query->leftJoin("{$this->getSourceDbName()}.content_type_documentation", 'content_type_documentation', "{$this->alias['node_documentation']}.vid = %alias.vid");
    $this->alias['state'] = $query->leftJoin("{$this->getSourceDbName()}.workflow_node", 'state', "{$this->alias['node']}.nid = %alias.nid");

    $this->alias['content_field_asset_sw_metrics'] = $query->leftJoin("{$this->getSourceDbName()}.content_field_asset_sw_metrics", 'content_field_asset_sw_metrics', "{$this->alias['node']}.vid = %alias.vid");
    $this->alias['node_metrics'] = $query->leftJoin("{$this->getSourceDbName()}.node", 'node_metrics', "{$this->alias['content_field_asset_sw_metrics']}.field_asset_sw_metrics_nid = %alias.nid");
    $this->alias['data_set_uri'] = $query->leftJoin("{$this->getSourceDbName()}.content_field_id_uri", 'data_set_uri', "{$this->alias['node_metrics']}.vid = %alias.vid");

    $query->addExpression("TRIM({$this->alias['uri']}.field_id_uri_value)", 'uri');
    $query->addExpression("TRIM({$this->alias['content_type_documentation']}.field_documentation_access_url1_url)", 'landing_page');
    $query->addExpression("FROM_UNIXTIME({$this->alias['node']}.created, '%Y-%m-%dT%H:%i:%s')", 'created');
    $query->addExpression("FROM_UNIXTIME({$this->alias['node']}.changed, '%Y-%m-%dT%H:%i:%s')", 'changed');
    $query->addExpression("TRIM({$this->alias['data_set_uri']}.field_id_uri_value)", 'metrics_page');

    return $query
      ->fields('j', ['policy'])
      ->fields($this->alias['node'], ['title', 'created', 'changed', 'vid'])
      ->fields($this->alias['node_revision'], ['body'])
      ->fields($this->alias['state'], ['sid']);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $nid = $row->getSourceProperty('nid');

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
    if (!$row->getSourceProperty('created')) {
      $row->setSourceProperty('created', date('Y-m-d\TH:i:s', REQUEST_TIME));
    }
    // Assure a changed date.
    if (!$row->getSourceProperty('changed')) {
      $row->setSourceProperty('changed', date('Y-m-d\TH:i:s', REQUEST_TIME));
    }

    // Extract keywords.
    $query = $this->select('term_node', 'tn');
    $query->join('term_data', 'td', 'tn.tid = td.tid');
    $keywords = $query
      ->fields('td', ['name'])
      ->condition('tn.nid', $nid)
      ->condition('tn.vid', $row->getSourceProperty('vid'))
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

    // Country.
    $row->setSourceProperty('country', $this->getCountries($row->getSourceProperty('vid')));

    // Status.
    $query = $this->select('term_node', 'tn');
    $query->join('term_data', 'td', 'tn.tid = td.tid');
    $status = $query
      ->fields('tn', ['tid'])
      ->condition('tn.nid', $nid)
      ->condition('tn.vid', $row->getSourceProperty('vid'))
      // The status vocabulary vid is 69.
      ->condition('td.vid', 69)
      ->orderBy('tn.tid', 'DESC')
      ->range(0, 1)
      ->execute()
      ->fetchCol();
    $row->setSourceProperty('status', $status);

    return parent::prepareRow($row);
  }

}
