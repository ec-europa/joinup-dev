<?php

namespace Drupal\pipeline\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\pipeline\PipelineOrchestratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Defines the pipeline execution controller.
 */
class PipelineExecutionController extends ControllerBase {

  /**
   * The pipeline orchestrator service.
   *
   * @var \Drupal\pipeline\PipelineOrchestratorInterface
   */
  protected $orchestrator;

  /**
   * Constructs a new pipeline execution controller.
   *
   * @param \Drupal\pipeline\PipelineOrchestratorInterface $orchestrator
   *   The pipeline orchestrator service.
   */
  public function __construct(PipelineOrchestratorInterface $orchestrator) {
    $this->orchestrator = $orchestrator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): PipelineExecutionController {
    return new static($container->get('pipeline.orchestrator'));
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
  public function execution($pipeline) {
    return $this->orchestrator->run($pipeline);
  }

  /**
   * Controller callback: Reset the state machine.
   *
   * @param string $pipeline
   *   The pipeline to reset.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response.
   */
  public function reset($pipeline) {
    $this->orchestrator->reset($pipeline);

    if ($this->currentUser()->hasPermission("access pipeline selector")) {
      $url = Url::fromRoute('pipeline.pipeline_select');
    }
    else {
      $url = Url::fromUri('internal:/<front>');
    }

    return new RedirectResponse($url->setAbsolute()->toString());
  }

  /**
   * Provides a custom access callback for the pipeline.execute_pipeline route.
   *
   * @param string $pipeline
   *   The pipeline plugin ID.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current use account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result object.
   */
  public function allowExecute($pipeline, AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, "execute $pipeline pipeline");
  }

  /**
   * Provides a custom access callback for the pipeline.reset_pipeline route.
   *
   * @param string $pipeline
   *   The pipeline plugin ID.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current use account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result object.
   */
  public function allowReset($pipeline, AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, "reset $pipeline pipeline");
  }

}
