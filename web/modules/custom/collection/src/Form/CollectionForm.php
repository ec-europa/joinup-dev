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
 * @ingroup collection
 */
class CollectionForm extends ContentEntityForm {
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\collection\Entity\Collection */
    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Collection.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Collection.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.collection.edit_form', ['collection' => $entity->id()]);
  }

}
