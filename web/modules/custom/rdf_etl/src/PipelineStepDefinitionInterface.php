<?php

declare(strict_types = 1);

namespace Drupal\rdf_etl;

/**
 * Class PipelineStepDefinition.
 *
 * @package Drupal\rdf_etl
 */
interface PipelineStepDefinitionInterface {

  /**
   * Register a callback.
   *
   * @param string $hook_name
   *   The hook name.
   * @param array $callback
   *   The callback definition.
   *
   * @return \Drupal\rdf_etl\PipelineStepDefinitionInterface
   *   Return $this for a fluent interface.
   *
   * @throws \Exception
   *   Thrown when registering a non-existing hook type.
   */
  public function registerHook(string $hook_name, array $callback): PipelineStepDefinitionInterface;

  /**
   * Invokes a callback on a pipeline.
   *
   * @param string $hook_name
   *   The hook name.
   * @param array $argument
   *   A container passed to the hook.
   *
   * @return array
   *   The argument, altered by the hook.
   *
   * @throws \Exception
   *   Thrown when trying to invoke a non-existing hook.
   */
  public function invokeHook(string $hook_name, array $argument): array;

  /**
   * Return the plugin id of the process step plugin to use for this step.
   *
   * @return string
   *   The plugin id.
   */
  public function getPluginId(): string;

}
