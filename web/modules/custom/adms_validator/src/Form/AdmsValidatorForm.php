<?php

declare(strict_types = 1);

namespace Drupal\adms_validator\Form;

use Drupal\adms_validator\AdmsValidatorInterface;
use Drupal\adms_validator\SchemaErrorList;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\FileInterface;
use EasyRdf\Graph;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to validate the ADMS compliance of a RDF or TTL file.
 */
class AdmsValidatorForm extends FormBase {

  /**
   * The ADMS validator service.
   *
   * @var \Drupal\adms_validator\AdmsValidatorInterface
   */
  protected $validator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('adms_validator.validator')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\adms_validator\AdmsValidatorInterface $validator
   *   The Sparql endpoint.
   */
  public function __construct(AdmsValidatorInterface $validator) {
    $this->validator = $validator;
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
  public function buildForm(array $form, FormStateInterface $form_state) : array {
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
    try {
      $graph = $this->fileToGraph($file);
    }
    catch (\Exception $e) {
      $form_state->setError($form['adms_file'], 'The provided file is not a valid RDF file.');
      $file->delete();
      return;
    }
    // Delete the uploaded file from disk.
    $file->delete();
    try {
      $this->validator->storeGraph($graph);
      $schema_errors = $this->validator->validateGraph();
    }
    catch (\Exception $e) {
      $form_state->setError($form['adms_file'], $e->getMessage());
      return;
    }
    if ($schema_errors->errorCount()) {
      drupal_set_message($this->t('%count schema error(s) were found while validating.', ['%count' => $schema_errors->errorCount()]), 'warning');
    }
    else {
      drupal_set_message($this->t('No errors found during validation.'));
    }
    $form_state->set('validation_errors', $schema_errors);
  }

  /**
   * Render the table with validation errors.
   *
   * @param \Drupal\adms_validator\SchemaErrorList $errors
   *   The validation errors.
   *
   * @return array
   *   The error table as render array.
   */
  protected function buildErrorTable(SchemaErrorList $errors) : array {
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
      '#rows' => $errors->toRows(),
    ];
  }

  /**
   * Retrieves the uploaded file.
   *
   * @return \Drupal\file\FileInterface|null
   *   File object, if one is uploaded.
   */
  protected function uploadedFile() : ?FileInterface {
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

  /**
   * Build a RDF graph from a file object.
   *
   * @param \Drupal\file\FileInterface $file
   *   The to be validated file.
   *
   * @return \EasyRdf\Graph
   *   A collection of triples.
   */
  protected function fileToGraph(FileInterface $file) : Graph {
    $graph = new Graph();
    $graph->parseFile($file->getFileUri());
    return $graph;
  }

}
