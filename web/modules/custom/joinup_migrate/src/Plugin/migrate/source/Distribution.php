<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\joinup_migrate\RedirectImportInterface;
use Drupal\migrate\Row;

/**
 * Provides a distribution migration source plugin.
 *
 * @MigrateSource(
 *   id = "distribution"
 * )
 */
class Distribution extends JoinupSqlBase implements RedirectImportInterface {

  use DefaultNodeRedirectTrait;
  use FileUrlFieldTrait;
  use LicenceTrait;
  use StatusTrait;

  /**
   * {@inheritdoc}
   */
  protected $reservedUriTables = ['collection', 'solution', 'release'];

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'nid' => [
        'type' => 'integer',
        'alias' => 'd',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'nid' => $this->t('Node ID'),
      'uri' => $this->t('URI'),
      'title' => $this->t('Name'),
      'access_url' => $this->t('Access URL'),
      'created_time' => $this->t('Created time'),
      'body' => $this->t('Description'),
      'licence' => $this->t('Licence'),
      'changed_time' => $this->t('Changed time'),
      'technique' => $this->t('Representation technique'),
      'status' => $this->t('Status'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('d8_distribution', 'd')->fields('d', [
      'nid',
      'uri',
      'vid',
      'title',
      'body',
      'created_time',
      'changed_time',
      'licence',
      'file_id',
      'access_url',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $nid = $row->getSourceProperty('nid');
    $vid = $row->getSourceProperty('vid');

    // Representation technique.
    $query = $this->select('term_node', 'tn');
    $query->join('term_data', 'td', 'tn.tid = td.tid');
    $representation_technique = $query
      ->fields('td', ['name'])
      ->condition('tn.nid', $nid)
      ->condition('tn.vid', $vid)
      // The representation technique vocabulary vid is 70.
      ->condition('td.vid', 70)
      ->execute()
      ->fetchCol();
    $row->setSourceProperty('technique', $representation_technique);

    // Resolve 'access_url'.
    $fid = $row->getSourceProperty('file_id');
    $file_source_id_values = $fid ? [['fid' => $fid]] : [];
    $urls = $row->getSourceProperty('access_url') ? [$row->getSourceProperty('access_url')] : [];
    $this->setFileUrlTargetId($row, 'access_url', $file_source_id_values, 'file:distribution', $urls);

    // Status.
    $this->setStatus($vid, $row);

    // Licence.
    $this->setLicence($row, 'distribution');

    return parent::prepareRow($row);
  }

}
