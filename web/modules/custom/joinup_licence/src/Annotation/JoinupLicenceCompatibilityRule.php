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
   * @var string
   */
  public $id;

  /**
   * The ID of the licence compatibility document that explains this rule.
   *
   * @var string
   */
  public $document_id;

  /**
   * The weight of this plugin relative to other plugins.
   *
   * @var int
   */
  public $weight = 0;

}
