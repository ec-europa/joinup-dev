<?php

namespace Drupal\joinup_core\Plugin\views\style;

use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * A Views style that renders markup for Bootstrap tabs.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "tiles",
 *   title = @Translation("Tiles"),
 *   help = @Translation("Uses the Tiles component."),
 *   theme = "joinup_tiles",
 *   display_types = {"normal"}
 * )
 */
class Tiles extends StylePluginBase {

  /**
   * Does the style plugin allows to use style plugins.
   *
   * @var bool
   */
  protected $usesRowPlugin = TRUE;

  /**
   * Does the style plugin support custom css class for the rows.
   *
   * @var bool
   */
  protected $usesRowClass = TRUE;

}
