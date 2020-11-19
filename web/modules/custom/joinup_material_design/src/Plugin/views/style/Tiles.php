<?php

declare(strict_types = 1);

namespace Drupal\joinup_material_design\Plugin\views\style;

use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * A Views style that renders markup for "Tiles".
 *
 * A "tile" is Joinup terminology for a Material Design "card".
 *
 * How to use this:
 * 1. Create a view that uses the 'Tiles' style (called 'format' in the UI).
 * 2. If needed implement a preprocess hook to massage the data. The following
 *    preprocess hooks are available, in order of precedence:
 *    - joinup_tiles__{view_name}__{display_name}
 *    - joinup_tiles__{display_name}
 *    - joinup_tiles__{view_name}
 *    The default preprocess hook is `joinup_tiles` (ref.
 *    template_preprocess_joinup_tiles()).
 * 3. Override the Twig templates if needed. The default implementation is in
 *    joinup-tiles.html.twig.
 *
 * For some examples see `joinup_theme_preprocess_joinup_tiles__*()` and the
 * corresponding Twig templates.
 *
 * @see template_preprocess_joinup_tiles()
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
