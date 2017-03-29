<?php

namespace Drupal\rdf_entity\Form;

use Drupal\Core\Entity\EntityDeleteForm;

/**
 * Provides a deletion confirmation form for taxonomy vocabulary.
 */
class RdfTypeDeleteForm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rdf_type_confirm_delete';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the RDF type %title?', ['%title' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t("Deleting a RDF type won't delete the data in the triple store! It will however delete all fields attached to this bundle.");
  }

  /**
   * {@inheritdoc}
   */
  protected function getDeletionMessage() {
    return $this->t('Deleted RDF type %name.', ['%name' => $this->entity->label()]);
  }

}
