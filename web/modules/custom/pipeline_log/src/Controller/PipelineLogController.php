<?php

declare(strict_types = 1);

namespace Drupal\pipeline_log\Controller;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\pipeline\Plugin\PipelinePipelinePluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Pipeline log routes.
 */
class PipelineLogController extends ControllerBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The pipeline plugin manager service.
   *
   * @var \Drupal\pipeline\Plugin\PipelinePipelinePluginManager
   */
  protected $pipelineManager;

  /**
   * The collection of key-value pairs of the pipeline_log module.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $pipelineCollection;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The controller constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value
   *   The key-value factory service.
   * @param \Drupal\pipeline\Plugin\PipelinePipelinePluginManager $plugin_manager_pipeline_pipeline
   *   The pipeline plugin manager service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(AccountInterface $current_user, KeyValueFactoryInterface $key_value, PipelinePipelinePluginManager $plugin_manager_pipeline_pipeline, TimeInterface $time) {
    $this->currentUser = $current_user;
    $this->pipelineCollection = $key_value->get('pipeline_log');
    $this->pipelineManager = $plugin_manager_pipeline_pipeline;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('keyvalue'),
      $container->get('plugin.manager.pipeline_pipeline'),
      $container->get('datetime.time')
    );
  }

  /**
   * Presents a list of pipelines and their last logged execution time.
   */
  public function build() {

    $rows = [];
    foreach ($this->pipelineManager->getDefinitions() as $plugin_id => $definition) {
      if (!$this->currentUser->hasPermission("execute {$plugin_id} pipeline")) {
        continue;
      }
      $last_execute_time = $this->pipelineCollection->get($plugin_id);
      // Convert to days or set to 'N/A'.
      $last_execute_time = $last_execute_time ? floor(($this->time->getRequestTime() - $last_execute_time) / 60 / 60 / 24) : 'Never';
      $rows[] = [
        $definition['label'],
        $last_execute_time,
      ];
    }

    $build['content'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Pipeline'),
        $this->t('Last executed in days ago'),
      ],
      '#rows' => $rows,
      '#attributes' => [
        'class' => ['pipeline-log-table'],
      ],
    ];

    return $build;
  }

}
