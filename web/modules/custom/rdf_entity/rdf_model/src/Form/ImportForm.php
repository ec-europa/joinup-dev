<?php

namespace Drupal\rdf_model\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use EasyRdf\Format;

/**
 * Configuration form for rdf_export module.
 *
 * @package Drupal\rdf_export\Form
 */
class ImportForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ontology_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $ontologies = $this->getOntologies();

    foreach ($this->getClasses() as $ontology_id => $classes) {
      // Create an ontology fieldset.
      if (!isset($form[$ontology_id])) {
        $form[$ontology_id]['#type'] = 'fieldset';
        $form[$ontology_id]['#title'] = isset($ontologies[$ontology_id]) ? $ontologies[$ontology_id]['label'] : $this->t("Unknown ontology %uri", ['%uri' => $ontology_id]);
        $form[$ontology_id]['#description'] = isset($ontologies[$ontology_id]) ? $ontologies[$ontology_id]['comment'] : "";
      }

      foreach ($classes as $class_id => $class) {
        $form[$ontology_id][$class_id]['#type'] = 'fieldset';
        $form[$ontology_id][$class_id]['#title'] = $class['label'];
        $form[$ontology_id][$class_id]['#description'] = $class['comment'];
      }
      $form[$ontology_id]['import'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Import'),
        '#options' => ['ontology' => $ontology_id],
      );
    }
    return $form;
  }

  protected function getOntologies() {
    $query = "SELECT ?ontology ?label ?comment
WHERE {
  ?ontology <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://www.w3.org/2002/07/owl#Ontology> .
  ?ontology <http://www.w3.org/2000/01/rdf-schema#label> ?label .
  ?ontology <http://www.w3.org/2000/01/rdf-schema#comment> ?comment .
  FILTER (lang(?label) = 'en') .
  FILTER (lang(?comment) = 'en') .
}";
    $sparql = \Drupal::getContainer()->get('sparql_endpoint');
    $ontology_results = $sparql->query($query);
    $ontology_list = [];
    foreach ($ontology_results as $ontology_result) {
      $ontology_list[(string) $ontology_result->ontology] = [
        'label' => (string) $ontology_result->label,
        'comment' => (string) $ontology_result->comment,
      ];
    }
    return $ontology_list;
  }

  protected function getClasses() {
    $query = "SELECT ?ontology ?class ?label ?comment
WHERE {
  ?class <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://www.w3.org/2002/07/owl#Class> .
  ?class <http://www.w3.org/2000/01/rdf-schema#isDefinedBy> ?ontology .
  ?class <http://www.w3.org/2000/01/rdf-schema#label> ?label .
  ?class <http://www.w3.org/2000/01/rdf-schema#comment> ?comment .
  FILTER (lang(?label) = 'en') .
  FILTER (lang(?comment) = 'en') .
}";
    $sparql = \Drupal::getContainer()->get('sparql_endpoint');
    $classes_results = $sparql->query($query);
    $class_list = [];
    foreach ($classes_results as $classes_result) {
      $class_list[(string) $classes_result->ontology][(string) $classes_result->class] = [
        'label' => (string) $classes_result->label,
        'comment' => (string) $classes_result->comment,
      ];
    }
    return $class_list;
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
    $triggering_element = $form_state->getTriggeringElement();
    $ontology = $triggering_element['#options']['ontology'];
   $a = 1;
  }

}
