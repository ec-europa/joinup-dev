<?php

namespace Drupal\adms_validator\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\FileInterface;
use Drupal\rdf_entity\Database\Driver\sparql\Connection;
use EasyRdf\Graph;
use EasyRdf\GraphStore;
use EasyRdf\Sparql\Result;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to validate the ADMS compliance of a RDF or TTL file.
 */
class AdmsValidatorForm extends FormBase {

  /**
   * The name of the graph used for validation.
   *
   * @var string
   */
  const VALIDATION_GRAPH = 'http://adms-validator/';

  /**
   * The path of the file that contains the validation rules.
   *
   * @var string
   */
  const SEMIC_VALIDATION_QUERY_PATH = "SEMICeu/adms-ap_validator/python-rule-generator/ADMS-AP Rules .txt";

  /**
   * The Sparql endpoint.
   *
   * @var \Drupal\rdf_entity\Database\Driver\sparql\Connection
   */
  protected $sparqlEndpoint;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('sparql_endpoint')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\rdf_entity\Database\Driver\sparql\Connection $sparql_endpoint
   *   The Sparql endpoint.
   */
  public function __construct(Connection $sparql_endpoint) {
    $this->sparqlEndpoint = $sparql_endpoint;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'adms_validator_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['adms_file'] = [
      '#type' => 'file',
      '#title' => $this->t('File'),
      '#description' => $this->t('An RDF file you want to test for compliance.'),
      '#upload_validators'  => [
        'file_validate_extensions' => ['rdf ttl'],
      ],
    ];
    $form['info'] = [
      '#markup' => $this->t('This validator uses the <a href="https://github.com/SEMICeu/adms-ap_validator">SEMIC ADMS-AP ruleset</a>.'),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Upload'),
      '#button_type' => 'primary',
    ];
    $validation_errors = $form_state->get('validation_errors');
    if (!empty($validation_errors)) {
      // The form was submitted, and validation errors have been set.
      $form['table'] = $this->buildErrorTable($validation_errors);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    // Make sure to clear the table.
    $form_state->set('validation_errors', []);
    $form['table'] = [];
    $form_state->setRebuild();

    $file = $this->uploadedFile();
    if (!$file) {
      $form_state->setError($form['adms_file'], 'Please upload a valid RDF file.');
      return;
    }
    $count = $this->storeInGraph($file);
    // Delete the uploaded file from disk.
    $file->delete();
    if (!$count) {
      $form_state->setError($form['adms_file'], 'The provided file is not a valid RDF file.');
      return;
    }
    drupal_set_message($this->t('Checking %count triples for schema errors.', ['%count' => $count]));
    $form_state->set('validation_errors', $this->getValidationErrors());
  }

  /**
   * Render the table with validation errors.
   *
   * @param \EasyRdf\Sparql\Result $errors
   *   The validation errors.
   *
   * @return array
   *   The error table as render array.
   */
  protected function buildErrorTable(Result $errors) {
    $rows = [];
    foreach ($errors as $error) {
      $row = [
        isset($error->Class_Name) ? $error->Class_Name : '',
        isset($error->Message) ? $error->Message : '',
        isset($error->Object) ? $error->Message : '',
        isset($error->Predicate) ? $error->Predicate : '',
        isset($error->Rule_Description) ? $error->Rule_Description : '',
        isset($error->Rule_ID) ? $error->Rule_ID : '',
        isset($error->Rule_Severity) ? $error->Rule_Severity : '',
        isset($error->Subject) ? $error->Subject : '',
      ];
      $row = array_map('strval', $row);
      $rows[] = $row;
    }
    return [
      '#theme' => 'table',
      '#header' => [
        ['data' => t('Class name')],
        ['data' => t('Message')],
        ['data' => t('Object')],
        ['data' => t('Predicate')],
        ['data' => t('Rule description')],
        ['data' => t('Rule ID')],
        ['data' => t('Rule severity')],
        ['data' => t('Subject')],
      ],
      '#rows' => $rows,
    ];
  }

  /**
   * Build the list of validation errors.
   *
   * @return \EasyRdf\Sparql\Result
   *   The validation errors.
   */
  protected function getValidationErrors() {
    $adms_ap_rules = DRUPAL_ROOT . "/../vendor/" . self::SEMIC_VALIDATION_QUERY_PATH;
    $query = file_get_contents($adms_ap_rules);
    // Fill in our validation graph in the query.
    $query = str_replace('GRAPH <@@@TOKEN-GRAPH@@@> {

UNION', "GRAPH <" . self::VALIDATION_GRAPH . "> { ", $query);
    // @todo Workaround for bug in validations query.
    // See https://github.com/SEMICeu/adms-ap_validator/issues/1
    $query = str_replace('FILTER(!EXISTS {?o a }).', 'FILTER(!EXISTS {?o a spdx:checksumValue}).', $query);
    return $this->sparqlEndpoint->query($query);
  }

  /**
   * Store the triples in the temporary graph.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file being processed.
   *
   * @return bool
   *   True if the store operation was successful, false otherwise.
   */
  protected function storeInGraph(FileInterface $file) {
    $connection_options = $this->sparqlEndpoint->getConnectionOptions();
    $connect_string = 'http://' . $connection_options['host'] . ':' . $connection_options['port'] . '/sparql-graph-crud';
    // Use a local SPARQL 1.1 Graph Store.
    $gs = new GraphStore($connect_string);
    $graph = new Graph();
    try {
      $graph->parseFile($file->getFileUri());
    }
    catch (\Exception $e) {
      return FALSE;
    }
    $out = $gs->replace($graph, self::VALIDATION_GRAPH);
    if (!$out->isSuccessful()) {
      return FALSE;
    }
    return $graph->countTriples();
  }

  /**
   * Retrieves the uploaded file.
   *
   * @return \Drupal\file\FileInterface|null
   *   File object, if one is uploaded.
   */
  protected function uploadedFile() {
    $files = file_save_upload('adms_file', ['file_validate_extensions' => [0 => 'rdf ttl']], 'public://');
    /** @var \Drupal\file\FileInterface $file */
    $file = $files[0];
    if (!is_object($file)) {
      return NULL;
    }
    return $file;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

}
