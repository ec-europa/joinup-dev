<?php

namespace Drupal\rdf_export\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use EasyRdf\Format;

/**
 * Configuration form for rdf_export module.
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
    $export_types = $this->config('rdf_export.settings')->get('export_types');
    // @todo: Check which format are supported by the server?
    $formats = Format::getFormats();
    $list = [];
    /** @var \EasyRdf\Format $format */
    foreach ($formats as $format) {
      if ($format->getSerialiserClass()) {
        $list[$format->getName()] = $format->getLabel();
      }
    }

    $form['export_types'] = [
      '#type' => 'select',
      '#title' => t('Export types'),
      '#options' => $list,
      '#multiple' => TRUE,
      '#default_value' => empty($export_types) ? [] : $export_types,
      '#description' => t('Select the export types for rdf entities.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->config('rdf_export.settings')->set('export_types', $form_state->getValue('export_types'));
    $config->save();
  }

}
