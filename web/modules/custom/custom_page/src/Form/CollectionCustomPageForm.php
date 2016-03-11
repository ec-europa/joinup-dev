<?php

/**
 * @file
 * Contains \Drupal\custom_page\Form\CustomPageCollectionForm.
 */

namespace Drupal\custom_page\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeForm;

/**
 * Class CollectionCustomPageForm.
 *
 * @package Drupal\custom_page\Form
 */
class CollectionCustomPageForm extends NodeForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'collection_custom_page';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
  }

}
