<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

/**
 * Provides a 'document' node migration source plugin.
 *
 * @MigrateSource(
 *   id = "document"
 * )
 */
class Document extends NodeBase {

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'document_type' => $this->t('Document type'),
      'publication_date' => $this->t('Publication date'),
    ] + parent::fields();
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('d8_document', 'n')->fields('n', [
      'document_type',
      'publication_date',
    ]);
  }

}
