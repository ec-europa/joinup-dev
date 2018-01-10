<?php

declare(strict_types = 1);

namespace Drupal\rdf_etl;

/**
 * Class PipelineStepDefinition.
 *
 * @package Drupal\rdf_etl
 */
class PipelineStepDefinition {
  protected $pluginId;

  protected $preExecute;

  protected $postExecute;

  protected $hooks;

  const VALID_HOOKS = [
    'pre_form_execution',
    'post_form_execution',
  ];

  /**
   * PipelineStepDefinition constructor.
   *
   * @param string $plugin_id
   *   The plugin id of the process step associated with this step.
   */
  public function __construct(string $plugin_id) {
    $this->pluginId = $plugin_id;
  }

  /**
   * Register a callback.
   *
   * @param string $hook_name
   *   The hook name.
   * @param array $callback
   *   The callback definition.
   *
   * @return \Drupal\rdf_etl\PipelineStepDefinition
   *   Return $this for a fluent interface.
   *
   * @throws \Exception
   */
  public function registerHook(string $hook_name, array $callback): PipelineStepDefinition {
    if (!in_array($hook_name, self::VALID_HOOKS)) {
      throw new \Exception('Attempt to register non-existing hook.');
    }
    $this->hooks[$hook_name] = $callback;
    return $this;
  }

  /**
   * Invokes a callback on a pipeline.
   *
   * @param string $hook_name
   *   The hook name.
   * @param array $argument
   *   A container passed to the hook.
   *
   * @return mixed
   *   The argument, altered by the hook.
   *
   * @throws \Exception
   */
  public function invokeHook(string $hook_name, array $argument): array {
    if (!in_array($hook_name, self::VALID_HOOKS)) {
      throw new \Exception('Attempt to invoke non-existing hook.');
    }
    if (empty($this->hooks[$hook_name])) {
      // The pipeline does not implement this method.
      return $argument;
    }
    $callback = $this->hooks[$hook_name];

    if (!is_callable($callback)) {
      throw new \Exception('Pipeline defines a callback for but does not implement it.');
    }
    $return = call_user_func_array($callback, [$argument]);
    if (empty($return)) {
      throw new \Exception("Callback should return the data array.");
    }
    return $return;
  }

  /**
   * Return the plugin id of the process step plugin to use for this step.
   */
  public function getPluginId(): string {
    return $this->pluginId;
  }

}
