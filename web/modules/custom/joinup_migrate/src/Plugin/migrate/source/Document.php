<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Provides a 'document' node migration source plugin.
 *
 * @MigrateSource(
 *   id = "document"
 * )
 */
class Document extends NodeBase {

  use CountryTrait;
  use FileUrlFieldTrait;
  use KeywordsTrait;

  /**
   * {@inheritdoc}
   */
  protected $uriProperties = ['original_url'];

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'collection' => $this->t('Collection'),
      'document_type' => $this->t('Document type'),
      'publication_date' => $this->t('Publication date'),
      'original_url' => $this->t('Original URL'),
      'field_file' => $this->t('File'),
      'keywords' => $this->t('Keywords'),
      'country' => $this->t('Spatial coverage'),
      'licence' => $this->t('Licence'),
    ] + parent::fields();
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('d8_document', 'n')->fields('n', [
      'document_type',
      'publication_date',
      'original_url',
      'file_path',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $nid = $row->getSourceProperty('nid');
    $vid = $row->getSourceProperty('vid');

    // Resolve 'field_file'.
    $this->setFileUrlTargetId($row, 'field_file', ['nid' => $nid], 'file_path', 'document_file', 'original_url');

    // Keywords.
    $this->setKeywords($row, 'keywords', $nid, $vid);

    // Spatial coverage.
    $row->setSourceProperty('country', $this->getCountries([$vid]));

    return parent::prepareRow($row);
  }

}
