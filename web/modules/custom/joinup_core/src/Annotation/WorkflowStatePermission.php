<?php

declare(strict_types = 1);

namespace Drupal\joinup_core\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines the annotation for the WorkflowStatePermission plugin type.
 *
 * @ingroup joinup_core
 * @Annotation
 */
class WorkflowStatePermission extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

}
