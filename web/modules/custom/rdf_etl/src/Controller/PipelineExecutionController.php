<?php

declare(strict_types = 1);

namespace Drupal\rdf_etl\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\rdf_etl\EtlOrchestratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class PipelineExecutionController.
 */
class PipelineExecutionController extends ControllerBase {

  /**
   * Drupal\rdf_etl\EtlOrchestrator definition.
   *
   * @var \Drupal\rdf_etl\EtlOrchestratorInterface
   */
  protected $rdfEtlOrchestrator;

  /**
   * Constructs a new PipelineExecutionController object.
   *
   * @param \Drupal\rdf_etl\EtlOrchestratorInterface $rdf_etl_orchestrator
   *   The Etl orchestrator.
   */
  public function __construct(EtlOrchestratorInterface $rdf_etl_orchestrator) {
    $this->rdfEtlOrchestrator = $rdf_etl_orchestrator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): PipelineExecutionController {
    return new static(
      $container->get('rdf_etl.orchestrator')
    );
  }

  /**
   * Executes the pipeline passed by the route.
   *
   * @param string $pipeline
   *   The pipeline to be executed.
   *
   * @return array
   *   Render array.
   */
  public function execution(string $pipeline) {
    return $this->rdfEtlOrchestrator->run($pipeline);
  }

  /**
   * Controller callback: Reset the state machine.
   *
   * Should not be used, unless something went really bad.
   *
   * @return array
   *   The render array.
   */
  public function reset(): array {
    $this->rdfEtlOrchestrator->reset();
    return ['#markup' => 'Orchestrator reset.'];
  }

}
