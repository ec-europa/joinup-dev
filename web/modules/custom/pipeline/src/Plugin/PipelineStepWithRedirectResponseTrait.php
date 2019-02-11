<?php

namespace Drupal\pipeline\Plugin;

use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides a reusable method for steps that are responding with a redirect.
 *
 * Some steps are expensive and cannot be implemented to run in a batch process.
 * Thus, we trigger a redirect to end the PHP script execution, so that we
 * overcome a PHP max execution time overflow.
 */
trait PipelineStepWithRedirectResponseTrait {

  /**
   * {@inheritdoc}
   */
  public function getResponse() {
    return new RedirectResponse(
      Url::fromRoute('pipeline.execute_pipeline.html', [
        'pipeline' => $this->pipeline->getPluginId(),
      ])->toString()
    );
  }

}
