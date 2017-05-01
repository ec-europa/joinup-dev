<?php

namespace Drupal\adms_validator\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\rdf_entity\Database\Driver\sparql\Connection;
use EasyRdf\Graph;
use EasyRdf\GraphStore;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AdmsValidatorForm.
 *
 * @package Drupal\adms_validator\Form
 */
class AdmsValidatorForm extends FormBase {
  const VALIDATION_GRAPH = 'http://adms-validator/';

  /**
   * The sparql endpoint.
   *
   * @var \Drupal\rdf_entity\Database\Driver\sparql\Connection
   */
  protected $sparqlendpoint;

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
   *   The connection.
   */
  public function __construct(Connection $sparql_endpoint) {
    $this->sparqlendpoint = $sparql_endpoint;
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
      '#title' => 'Rdf file to validate',
      '#upload_validators'  => [
        'file_validate_extensions' => ['rdf ttl'],
      ],
    ];
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Upload'),
      '#button_type' => 'primary',
    ];

    $rows = [];
    $header = [
      ['data' => t('Class name')],
      ['data' => t('Message')],
      ['data' => t('Object')],
      ['data' => t('Predicate')],
      ['data' => t('Rule description')],
      ['data' => t('Rule ID')],
      ['data' => t('Rule severity')],
      ['data' => t('Subject')],
    ];
    $info = $form_state->getBuildInfo();
    if (!empty($info['result'])) {
      foreach ($info['result'] as $error) {
        $row = [
          $error->Class_Name ?? '',
          $error->Message ?? '',
          $error->Object ?? '',
          $error->Predicate ?? '',
          $error->Rule_Description ?? '',
          $error->Rule_ID ?? '',
          $error->Rule_Severity ?? '',
          $error->Subject ?? '',
        ];
        $row = array_map('strval', $row);
        $rows[] = $row;
      }
    }
    $form['table'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);
    $files = file_save_upload('adms_file', ['file_validate_extensions' => [0 => 'rdf ttl']], 'public://');
    /** @var \Drupal\file\FileInterface $file */
    $file = $files[0];
    if (!is_object($file)) {
      return;
    }

    $uri = $file->getFileUri();
    // Use a local SPARQL 1.1 Graph Store.
    // @todo Inject connection.
    $gs = new GraphStore('http://127.0.0.1:8890/sparql-graph-crud');

    $graph = new Graph();
    // @todo This will prob throw errors when not an rdf file...
    $graph->parseFile($uri);
    $gs->replace($graph, self::VALIDATION_GRAPH);
    $adms_ap_rules = DRUPAL_ROOT . "/../vendor/SEMICeu/adms-ap_validator/python-rule-generator/ADMS-AP Rules .txt";
    $query = file_get_contents($adms_ap_rules);
    // @todo Workaround for bug in validations query. Fix upstream.
    $query = str_replace('GRAPH <@@@TOKEN-GRAPH@@@> {

UNION', "GRAPH <" . self::VALIDATION_GRAPH . "> { ", $query);
    // @todo Workaround for bug in validations query. Fix upstream.
    $query = str_replace('FILTER(!EXISTS {?o a }).', 'FILTER(!EXISTS {?o a spdx:checksumValue}).', $query);
    $result = $this->sparqlendpoint->query($query);
    $form_state->addBuildInfo('result', $result);
  }

}
