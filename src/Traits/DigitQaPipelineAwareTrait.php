<?php

declare(strict_types = 1);

namespace Joinup\Traits;

/**
 * Reusable methods related to Digit QA Pipeline.
 */
trait DigitQaPipelineAwareTrait {

  /**
   * Checks if we're running inside DIGIT QA GitLab pipeline context.
   *
   * @return bool
   *   TRUE if we're running in DIGIT QA GitLab pipeline.
   */
  protected static function isDigitQaPipeline(): bool {
    // @todo Add more checks.
    return getenv('GITLAB_CI') === 'true' && getenv('TOOLKIT_PROJECT_ID') === 'digit-joinup';
  }

}
