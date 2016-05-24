<?php

namespace Drupal\rdf_export\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use EasyRdf\Format;

/**
 * Class SettingsForm.
 *
 * @package Drupal\rdf_export\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'rdf_export.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('rdf_export.settings');
    $export_types = $config->get('export_types');
    // @todo: Check which format are supported by the server?
    $formats = Format::getFormats();
    $list = [];
    /** @var \EasyRdf\Format $format */
    foreach ($formats as $format) {
      if ($format->getSerialiserClass()) {
        $list[$format->getName()] = $format->getLabel();
      }
    }

    $form['export_types'] = array(
      '#type' => 'select',
      '#title' => t('Export types'),
      '#options' => $list,
      '#multiple' => TRUE,
      '#default_value' => empty($export_types) ? [] : $export_types,
      '#description' => t('Select the export types for rdf entities.'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->config('rdf_export.settings');
    $config->set('export_types', $form_state->getValue('export_types'));
    $config->save();
  }

}
