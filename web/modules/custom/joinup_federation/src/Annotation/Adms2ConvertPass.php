<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a plugin that performs a ADMS v1 to v2 transformation.
 *
 * @see \Drupal\pipeline\JoinupFederationAdms2ConvertPassPluginBase
 * @see plugin_api
 *
 * @Annotation
 */
class Adms2ConvertPass extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The weight of this plugin.
   *
   * The plugin manager will use this definition entry to create an ordered list
   * of plugins, so passes precedence can be determined. If is missed, the 0
   * value is assumed.
   *
   * @var int
   */
  public $weight = 0;

}
