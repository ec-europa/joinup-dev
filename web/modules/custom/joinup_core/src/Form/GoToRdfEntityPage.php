<?php

namespace Drupal\joinup_core\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\rdf_entity\Entity\Rdf;

/**
 * Simple form that redirects to a RDF entity page.
 */
class GoToRdfEntityPage extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['rdf_entity_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('RDF entity ID'),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Go!'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    if (!Rdf::load($form_state->getValue('rdf_entity_id'))) {
      $form_state->setErrorByName('id', $this->t('Not a valid ID: :id.', [':id' => $form_state->getValue('rdf_entity_id')]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('entity.rdf_entity.canonical', [
      'rdf_entity' => $form_state->getValue('rdf_entity_id'),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'go_to_rdf_entity_page';
  }

}
