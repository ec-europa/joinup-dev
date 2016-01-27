<?php
/**
 * @file
 * Joinup install profile.
 */

use  \Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_form_FORMID_alter().
 *
 * Add the Sparql endpoint fields to the configure database install step.
 */
function joinup_form_install_settings_form_alter(&$form, FormStateInterface $form_state) {
  $form['sparql'] = array(
    '#type' => 'fieldset',
    '#title' => 'Sparql endpoint',
    '#tree' => TRUE,
  );
  $form['sparql']['host'] = array(
    '#type' => 'textfield',
    '#title' => 'Host',
    '#default_value' => 'localhost',
    '#size' => 45,
    '#required' => TRUE,
  );
  $form['sparql']['port'] = array(
    '#type' => 'number',
    '#title' => 'Port',
    '#default_value' => '8890',
    '#min' => 0,
    '#max' => 65535,
    '#required' => TRUE,
  );

  $form['actions']['save']['#limit_validation_errors'][] = ['sparql'];
  $form['actions']['save']['#submit'][] = 'joinup_form_install_settings_form_save';
}

/**
 * Submit callback: Save the Sparql connection string to the settings file.
 */
function joinup_form_install_settings_form_save($form, FormStateInterface $form_state) {
  $host = $form_state->getValue(['sparql', 'host']);
  $port = $form_state->getValue(['sparql', 'port']);
  $database = array(
    'prefix' => '',
    'host' => $host,
    'port' => $port,
    'namespace' => 'Drupal\\rdf_entity\\Database\\Driver\\sparql',
    'driver' => 'sparql',
  );
  $settings['databases']['default']['sparql'] = (object) array(
    'value' => $database,
    'required' => TRUE,
  );
  drupal_rewrite_settings($settings);
}
