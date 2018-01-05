<?php

namespace Drupal\joinup\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * A simple page that presents a TCA form for the collection creation.
 *
 * @package Drupal\joinup\Form
 */
class TcaForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'collection_tca_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['warning'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('In order to create the Collection you need first check the field below and then press the <em>Yes</em> button to proceed.'),
    ];

    $form['collection_tca'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('I understand and I commit to manage my collection on a regular basis.'),
      '#default_value' => FALSE,
    ];

    $form['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('No thanks'),
      '#limit_validation_errors' => [],
      '#submit' => ['::cancelSubmit'],
    ];

    $form['create'] = [
      '#type' => 'submit',
      '#value' => $this->t('Yes'),
      '#states' => [
        'disabled' => [
          ':input[name="collection_tca"]' => ['checked' => FALSE],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('collection_tca') === 0) {
      $form_state->setError($form['collection_tca'], 'You have to agree that you will manage your collection on a regular basis.');
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * Submit handler for the 'No thanks' button.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function cancelSubmit(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect(Url::fromUri('internal:/collections')->getRouteName());
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('rdf_entity.propose_form', [
      'rdf_type' => 'collection',
    ]);
  }

}
