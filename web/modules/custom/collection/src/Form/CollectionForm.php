<?php

/**
 * @file
 * Contains \Drupal\collection\Form\CollectionForm.
 */

namespace Drupal\collection\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Collection edit forms.
 *
 * @deprecated Will be replaced by a view in ISAICP-2205.
 *
 * @ingroup collection
 */
class CollectionForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $status = parent::save($form, $form_state);

    if ($status === SAVED_NEW) {
      drupal_set_message($this->t('Created the %label Collection.', [
        '%label' => $entity->label(),
      ]));
    }
    else {
      drupal_set_message($this->t('Saved the %label Collection.', [
        '%label' => $entity->label(),
      ]));
    }
    $form_state->setRedirect('entity.collection.edit_form', ['collection' => $entity->id()]);
  }

}
