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

    // Skip validation of cardinality for Sparql backend fields if the field
    // mapping is not provided. If a specific value is entered for the
    // cardinality, the default field storage validate handler will do a check
    // if there are any field instances in use in the database with a higher
    // cardinality. If this is the case it will throw a validation error, so no
    // data is lost. When a brand new Sparql based field is being created, the
    // RDF field mapping is not yet stored in the third party settings, so the
    // database cannot be queried yet. This will cause an exception to be
    // thrown. Let's avoid this from happening.
    if ($this->entityTypeManager->getStorage($type) instanceof RdfEntitySparqlStorage) {
      // Check if the RDF field mapping already exists. If it doesn't skip the
      // part of the storage form validation that checks the database by
      // tricking it in thinking the entity is new.
      if (!$this->hasRdfFieldMapping()) {
        $this->entity->enforceIsNew(TRUE);
        parent::validateForm($form, $form_state);
        $this->entity->enforceIsNew(FALSE);
        return;
      }
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * Returns whether the field has a populated RDF field mapping value.
   *
   * @return bool
   *   Whether or not the RDF field mapping has been populated.
   */
  protected function hasRdfFieldMapping() {
    /** @var \Drupal\field\FieldStorageConfigInterface $unchanged_entity */
    $unchanged_entity = $this->entityTypeManager->getStorage('field_storage_config')->loadUnchanged($this->entity->id());
    return !empty($unchanged_entity->getThirdPartySetting('rdf_entity', 'mapping', [])['value']);
  }

}
