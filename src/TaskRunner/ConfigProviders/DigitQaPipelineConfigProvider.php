<?php

declare(strict_types = 1);

namespace Joinup\TaskRunner\ConfigProviders;

use Joinup\Traits\DigitQaPipelineAwareTrait;
use OpenEuropa\TaskRunner\Contract\ConfigProviderInterface;
use OpenEuropa\TaskRunner\Traits\ConfigFromFilesTrait;
use Robo\Config\Config;

/**
 * Provides Task Runner configs inside the Digit QA pipeline.
 *
 * This provider should run just after JoinupConfigProvider, which has '0' as
 * priority.
 *
 * @priority -10
 *
 * @see \Joinup\TaskRunner\ConfigProviders\JoinupConfigProvider
 */
class DigitQaPipelineConfigProvider implements ConfigProviderInterface {

  use ConfigFromFilesTrait;
  use DigitQaPipelineAwareTrait;

  /**
   * {@inheritdoc}
   */
  public static function provide(Config $config): void {
    if (static::isDigitQaPipeline()) {
      // Import configurations from ./resources/runner/digit_qa_pipeline/.
      static::importFromFiles($config, glob('resources/runner/digit_qa_pipeline/*.yml'));
    }
  }

}
