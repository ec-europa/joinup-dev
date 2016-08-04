<?php

namespace Drupal\rdf_entity\Form;
use \Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class GraphSelectForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $bi = $form_state->getBuildInfo();
    $options = $bi['args'][0];
    if (count($options) <= 1) {
      return $form;
    }
    $default = NULL;
    if (!empty($_GET['graph'])) {
      if (is_string($_GET['graph']) && isset($options[$_GET['graph']])) {
        $default = $_GET['graph'];
      }
    }
    $form['graph'] = [
      '#type' => 'select',
      '#title' => $this->t('Graph'),
      '#options' => $options,
      '#default_value' => $default,
      '#description' => $this->t('The graph to load the entity from.'),
    ];
    $form['submit'] = [
      '#value' => $this->t('Select graph'),
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