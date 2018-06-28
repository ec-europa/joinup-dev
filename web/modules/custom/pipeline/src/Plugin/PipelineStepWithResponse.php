<?php

namespace Drupal\pipeline\Plugin;

/**
 * Provides an interface for pipeline steps returning a HTTP response.
 */
interface PipelineStepWithResponse extends PipelineStepInterface {

  /**
   * Returns a response as a render array or a redirect.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   The response.
   */
  public function getResponse();

}
