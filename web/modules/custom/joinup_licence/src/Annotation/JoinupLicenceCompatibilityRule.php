<?php

declare(strict_types = 1);

namespace Drupal\joinup_licence\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * The annotation for licence compatibility rule plugins.
 *
 * @Annotation
 */
class JoinupLicenceCompatibilityRule extends Plugin {

  /**
   * The plugin ID.
   *
   * Acts also as ID of the licence compatibility document that explains
   * this rule.
   *
   * @var string
   */
  public $id;

  /**
   * The weight of this plugin relative to other plugins.
   *
   * @var int
   */
  public $weight = 0;

}
