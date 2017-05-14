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
  use FileUrlFieldTrait;
  use KeywordsTrait;
  use StateTrait;
  use StatusTrait;

  /**
   * {@inheritdoc}
   */
  protected $reservedUriTables = ['collection'];

  /**
   * {@inheritdoc}
   */
  protected $uriProperties = ['uri', 'landing_page', 'metrics_page'];

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
      'documentation' => $this->t('Documentation'),
      'state' => $this->t('State'),
      'item_state' => $this->t('Item state'),
    ] + parent::fields();
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    return parent::query()->fields('s', [
      'vid',
      'type',
      'title',
      'uri',
      'created_time',
      'changed_time',
      'body',
      'policy2',
      'landing_page',
      'metrics_page',
      'docs_url',
      'docs_path',
      'state',
      'item_state',
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

    // Resolve documentation.
    $file_source_id_values = $row->getSourceProperty('docs_path') ? [['nid' => $nid]] : [];
    $this->setFileUrlTargetId($row, 'documentation', $file_source_id_values, 'documentation_file', 'docs_url');

    // Spatial coverage.
    $row->setSourceProperty('country', $this->getCountries([$vid]));

    // Owners.
    $owner = $this->select('d8_owner_solution', 'o')
      ->fields('o', ['nid'])
      ->condition('o.solution', $nid)
      ->execute()
      ->fetchCol();
    $row->setSourceProperty('owner', $owner);

    // Contacts.
    $contact = $this->select('d8_contact_solution', 'c')
      ->fields('c', ['nid'])
      ->condition('c.solution', $nid)
      ->execute()
      ->fetchCol();
    $row->setSourceProperty('contact', $contact);

    // Distributions.
    $query = $this->select('content_field_asset_distribution', 'd')
      ->fields('n', ['nid'])
      ->condition('d.vid', $vid);
    $query->join('node', 'n', 'd.field_asset_distribution_nid = n.nid');
    $distributions = $query->execute()->fetchCol();
    $row->setSourceProperty('distribution', $distributions);

    // Status.
    $this->setStatus($vid, $row);

    // State.
    $this->setState($row);

    return parent::prepareRow($row);
  }

}
