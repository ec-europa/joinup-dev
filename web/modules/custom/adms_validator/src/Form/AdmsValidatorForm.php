<?php

declare(strict_types = 1);

namespace Drupal\adms_validator\Form;

use Drupal\adms_validator\AdmsValidatorInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\FileInterface;
use Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Form to validate the ADMS compliance of a RDF or TTL file.
 */
class AdmsValidatorForm extends FormBase {

  /**
   * The ADMS validator service.
   *
   * @var \Drupal\adms_validator\AdmsValidatorInterface
   */
  protected $admsValidator;

  /**
   * Current user session ID.
   *
   * @var string
   */
  protected $sessionId;

  /**
   * The SPARQL endpoint.
   *
   * @var \Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface
   */
  protected $sparql;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('adms_validator.validator'),
      $container->get('session'),
      $container->get('sparql.endpoint')
    );
  }

  /**
   * Builds a new service instance.
   *
   * @param \Drupal\adms_validator\AdmsValidatorInterface $adms_validator
   *   The ADMS validator service.
   * @param \Symfony\Component\HttpFoundation\Session\Session $session
   *   The current user session.
   * @param \Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface $sparql
   *   The SPARQL endpoint.
   */
  public function __construct(AdmsValidatorInterface $adms_validator, Session $session, ConnectionInterface $sparql) {
    $this->admsValidator = $adms_validator;
    $this->sessionId = $session->getId();
    $this->sparql = $sparql;
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
  public function buildForm(array $form, FormStateInterface $form_state): array {
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
    /** @var \Drupal\adms_validator\AdmsValidationResult $validation_errors */
    $validation_errors = $form_state->get('validation_errors');
    if (!empty($validation_errors)) {
      // The form was submitted, and validation errors have been set.
      $form['table'] = $validation_errors->toTable();
    }

    honeypot_add_form_protection($form, $form_state, ['honeypot', 'time_restriction']);

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
      $uri = AdmsValidatorInterface::DEFAULT_VALIDATION_GRAPH . "/{$this->sessionId}";
      $schema_errors = $this->admsValidator->validateFile($file->getFileUri(), $uri);
    }
    catch (\Exception $e) {
      $form_state->setError($form['adms_file'], $e->getMessage());
      return;
    }

    // Delete the uploaded file from disk.
    $file->delete();
    // Clear the graph.
    $this->sparql->query("CLEAR GRAPH <$uri>");

    if ($schema_errors->isSuccessful()) {
      drupal_set_message($this->t('No errors found during validation.'));
    }
    else {
      drupal_set_message($this->t('%count schema error(s) were found while validating.', ['%count' => $schema_errors->errorCount()]), 'warning');
    }
    $form_state->set('validation_errors', $schema_errors);
  }

  /**
   * Retrieves the uploaded file.
   *
   * @return \Drupal\file\FileInterface|null
   *   File object, if one is uploaded.
   */
  protected function uploadedFile(): ?FileInterface {
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
