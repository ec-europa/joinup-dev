<?php

/**
 * @file
 * Contains \Drupal\custom_page\Form\CustomPageCollectionForm.
 */

namespace Drupal\custom_page\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class CustomPageCollectionForm.
 *
 * @package Drupal\custom_page\Form
 */
class CustomPageCollectionForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'custom_page_collection_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
