<?php

namespace Drupal\rdf_entity\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\field_ui\Form\FieldStorageConfigEditForm;
use Drupal\rdf_entity\Entity\RdfEntitySparqlStorage;

/**
 * Provides a form for the "field storage" edit page.
 */
class RdfFieldStorageConfigEditForm extends FieldStorageConfigEditForm {

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $type = $this->entity->get('entity_type');
    // Skip validation of cardinality for Sparql backend fields.
    if ((\Drupal::entityManager()->getStorage($type)) instanceof RdfEntitySparqlStorage) {
      return;
    }
    parent::validateForm($form, $form_state);
  }

}
