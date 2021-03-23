<?php

namespace Drupal\sparql_entity_storage\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\field_ui\Form\FieldStorageConfigEditForm;

/**
 * Provides a form for the "field storage" edit page.
 */
class SparqlEntityStorageFieldStorageConfigEditForm extends FieldStorageConfigEditForm {

  /**
   * {@inheritdoc}
   *
   * Override the cardinality validation because the SPARQL storage is not yet
   * aware of the %delta "column" as multiple cardinality is not yet supported
   * with field mappings that contain multiple columns.
   */
  public function validateCardinality(array &$element, FormStateInterface $form_state) {
    // Validate field cardinality.
    if ($form_state->getValue('cardinality') === 'number' && !$form_state->getValue('cardinality_number')) {
      $form_state->setError($element['cardinality_number'], $this->t('Number of values is required.'));
    }
  }

}
