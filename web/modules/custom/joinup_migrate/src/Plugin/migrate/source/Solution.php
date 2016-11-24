<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\Component\Utility\UrlHelper;
use Drupal\migrate\Row;

/**
 * Migrates solutions.
 *
 * @MigrateSource(
 *   id = "solution"
 * )
 */
class Solution extends SolutionBase {

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

    $query->addExpression("{$this->alias['uri']}.field_id_uri_value", 'uri');
    $query->addExpression("{$this->alias['content_type_documentation']}.field_documentation_access_url1_url", 'landing_page');
    $query->addExpression("FROM_UNIXTIME({$this->alias['node']}.created, '%Y-%m-%dT%H:%i:%s')", 'created');
    $query->addExpression("FROM_UNIXTIME({$this->alias['node']}.changed, '%Y-%m-%dT%H:%i:%s')", 'changed');

    return $query
      ->fields($this->alias['node'], ['title', 'created', 'changed', 'vid'])
      ->fields($this->alias['node_revision'], ['body']);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {

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
      ->condition('tn.nid', $row->getSourceProperty('nid'))
      ->condition('tn.vid', $row->getSourceProperty('vid'))
      // The keywords vocabulary vid is 28.
      ->condition('td.vid', 28)
      ->execute()
      ->fetchCol();
    $row->setSourceProperty('keywords', array_unique($keywords));

    // Filter and fix landing page.
    if ($landing_page = $row->getSourceProperty('landing_page')) {
      // Don't import malformed URLs.
      if (!UrlHelper::isValid($landing_page)) {
        $landing_page = NULL;
      }
      $url = parse_url($landing_page);
      if (empty($url['scheme'])) {
        // Needs a full-qualified URL.
        $landing_page = "http://$landing_page";
      }
      // Don't allow internal landing pages.
      if ($url['host'] !== 'joinup.ec.europa.eu') {
        $row->setSourceProperty('landing_page', $landing_page);
      }
    }

    return parent::prepareRow($row);
  }

}
