<?php

namespace Drupal\rdf_etl\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\rdf_etl\EtlOrchestrator;

/**
 * Class PipelineExecutionController.
 */
class PipelineExecutionController extends ControllerBase {

  /**
   * Drupal\rdf_etl\EtlOrchestrator definition.
   *
   * @var \Drupal\rdf_etl\EtlOrchestrator
   */
  protected $rdfEtlOrchestrator;

  /**
   * Constructs a new PipelineExecutionController object.
   */
  public function __construct(EtlOrchestrator $rdf_etl_orchestrator) {
    $this->rdfEtlOrchestrator = $rdf_etl_orchestrator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('rdf_etl.orchestrator')
    );
  }

  /**
   * Execution.
   *
   * @return array
   *   Render array.
   */
  public function execution() {
    return $this->rdfEtlOrchestrator->run();
  }

}
