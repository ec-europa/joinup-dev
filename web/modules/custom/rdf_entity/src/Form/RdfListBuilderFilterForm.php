<?php

namespace Drupal\rdf_entity\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form to select which graph to load from in the entity listing page.
 */
class RdfListBuilderFilterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $bi = $form_state->getBuildInfo();
    $options = $bi['args'][0];
    if (count($options) <= 1) {
      return $form;
    }

    /** @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info */
    $bundle_info = \Drupal::service('entity_type.bundle.info');
    $request = \Drupal::request();

    $form['inline'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['container-inline']],
    ];
    $form['inline']['graph'] = [
      '#type' => 'select',
      '#title' => $this->t('Graph'),
      '#options' => $options,
      '#default_value' => $request->get('graph'),
    ];
    $form['inline']['rid'] = [
      '#type' => 'select',
      '#title' => $this->t('Bundle'),
      '#options' => array_map(function (array $info) {
        return $info['label'];
      }, $bundle_info->getBundleInfo('rdf_entity')),
      '#default_value' => $request->get('rid'),
      '#empty_value' => NULL,
      '#empty_option' => $this->t('- All -'),
    ];
    $form['inline']['submit'] = [
      '#value' => $this->t('Filter'),
      '#type' => 'submit',
    ];
    $form['#method'] = 'get';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'graph_select_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Required by interface, but never called due to GET method.
  }

}
