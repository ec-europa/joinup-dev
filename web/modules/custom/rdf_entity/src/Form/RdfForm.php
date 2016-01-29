<?php
/**
 * @file
 * Contains Drupal\rdf_entity\Form\RdfForm.
 */

namespace Drupal\rdf_entity\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Language\Language;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the rdf_entity entity edit forms.
 *
 * @ingroup rdf_entity
 */
class RdfForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\rdf_entity\Entity\Rdf */
    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;

    $form['langcode'] = array(
      '#title' => $this->t('Language'),
      '#type' => 'language_select',
      '#default_value' => $entity->getUntranslated()->language()->getId(),
      '#languages' => Language::STATE_ALL,
    );
    if (!$entity->isNew()) {
      $form['id']['#disabled'] = TRUE;
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $form_state->setRedirect('entity.rdf_entity.collection');
    $entity = $this->getEntity();
    $entity->save();
  }

}
