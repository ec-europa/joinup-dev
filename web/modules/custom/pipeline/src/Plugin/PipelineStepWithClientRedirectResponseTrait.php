<?php

namespace Drupal\pipeline\Plugin;

/**
 * Provides a reusable method for steps that are responding with a redirect.
 *
 * Some steps can be run in a single iteration but they might add up to one big
 * request if many of them stack up. This response will result into an html
 * redirect that will refresh the request eventually.
 *
 * @see \Drupal\pipeline\Plugin\PipelineStepWithClientRedirectResponseTrait
 */
trait PipelineStepWithClientRedirectResponseTrait {

  /**
   * {@inheritdoc}
   */
  public function getResponse() {
    return [
      '#attached' => [
        'html_head' => [
          [
            0 =>  [
              '#tag' => 'meta',
              '#attributes' => [
                'http-equiv' => 'Refresh',
                'content' => '0; URL=' . \Drupal::service('path.current')->getPath(),
              ],
            ],
            1 => 'joinup_federation',
          ],
        ],
      ],
      '#title' => $this->getPageTitle(),
      [
        // Even though this is shown in the end of the step, the user only sees
        // a flow of iterations i.e. what is being run. It is more user friendly
        // to provide the user with information of what is running.
        '#markup' => t('Finished step %step.', ['%step' => $this->getPluginDefinition()['label']]),
      ]
    ];
  }

}
