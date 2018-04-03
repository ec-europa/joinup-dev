<?php

namespace Drupal\pipeline\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\pipeline\PipelineOrchestratorInterface;
use Drupal\pipeline\PipelineStateManagerInterface;
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
   * The state manager service.
   *
   * @var \Drupal\pipeline\PipelineStateManager
   */
  protected $stateManager;

  /**
   * Constructs a new PipelineExecutionController object.
   *
   * @param \Drupal\pipeline\PipelineOrchestratorInterface $orchestrator
   *   The pipeline orchestrator service.
   * @param \Drupal\pipeline\PipelineStateManagerInterface $state_manager
   *   The state manager service.
   */
  public function __construct(PipelineOrchestratorInterface $orchestrator, PipelineStateManagerInterface $state_manager) {
    $this->orchestrator = $orchestrator;
    $this->stateManager = $state_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): PipelineExecutionController {
    return new static(
      $container->get('pipeline.orchestrator'),
      $container->get('pipeline.state_manager'),
      $container->get('current_route_match')
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
  public function execution($pipeline) {
    return $this->orchestrator->run($pipeline);
  }

  /**
   * Controller callback: Reset the state machine.
   *
   * Should not be used, unless something went really bad.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response.
   */
  public function reset() {
    $this->orchestrator->reset();

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
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current use account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result object.
   */
  public function allowReset(AccountInterface $account) {
    if (!$this->stateManager->isPersisted()) {
      // No state is persisted. Don't make any decision.
      return AccessResult::neutral();
    }
    $pipeline = $this->stateManager->state()->getPipelineId();
    // Normally, it's not possible that user has to reset a pipeline on which he
    // has no permission because he could not instantiated it.
    return AccessResult::allowedIfHasPermission($account, "execute $pipeline pipeline");
  }

}
