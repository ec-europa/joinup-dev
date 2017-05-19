<?php

namespace Drupal\asset_distribution\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Retrieves the e-mail from anonymous users that are downloading distributions.
 */
class AnonymousDownloadForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'anonymous_download_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('E-mail address'),
      '#description' => $this->t('Your e-mail address as <em>john.doe@domain.com</em>'),
      '#maxlength' => 180,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // @todo Implement this.
  }

}
