<?php

namespace Drupal\rdf_draft\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\rdf_entity\Entity\RdfEntitySparqlStorage;

/**
 * Configuration form for rdf_export module.
 *
 * @package Drupal\rdf_export\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'rdf_draft.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rdf_draft_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $definitions = \Drupal::entityTypeManager()->getDefinitions();
    foreach ($definitions as $name => $definition) {
      $storage = \Drupal::entityManager()->getStorage($name);
      if ($storage instanceof RdfEntitySparqlStorage) {
        $enabled_bundles = $this->config('rdf_draft.settings')->get('revision_bundle_' . $name);
        $bundle_type = $definition->getBundleEntityType();
        $bundles = \Drupal::entityTypeManager()->getStorage($bundle_type)->loadMultiple();
        $options = [];
        foreach ($bundles as $bundle_name => $bundle) {
          $options[$bundle_name] = $bundle->label();
        }
        $form['revision_bundles:' . $name] = [
          '#type' => 'checkboxes',
          '#title' => $this->t('Enable save as draft for %entity_type bundles', ['%entity_type' => $name]),
          '#options' => $options,
        ];
        if ($enabled_bundles) {
          $form['revision_bundles:' . $name]['#default_value'] = $enabled_bundles;
        }
        $graphs = [];
        /** @var ContentEntityType $def */
        foreach ($storage->getGraphDefinitions() as $key => $def) {
          $graphs[$key] = $def['title'];
        }
        $default_save_graph = $this->config('rdf_draft.settings')->get('default_save_graph_' . $name);
        $form['default_save_graph:' . $name] = [
          '#title' => $this->t('Default graph to save to'),
          '#type' => 'select',
          '#options' => $graphs,
        ];
        if ($default_save_graph) {
          $form['default_save_graph:' . $name]['#default_value'] = $default_save_graph;
        }
      }
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $definitions = \Drupal::entityTypeManager()->getDefinitions();
    foreach ($definitions as $name => $definition) {
      $storage = \Drupal::entityManager()->getStorage($name);
      if ($storage instanceof RdfEntitySparqlStorage) {
        $config = $this->config('rdf_draft.settings')
          ->set('revision_bundle_' . $name, $form_state->getValue('revision_bundles:' . $name))
          ->set('default_save_graph_' . $name, $form_state->getValue('default_save_graph:' . $name));
        $config->save();
      }
    }
  }

}
