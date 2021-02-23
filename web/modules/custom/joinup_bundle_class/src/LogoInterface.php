<?php

declare(strict_types = 1);

namespace Drupal\joinup_bundle_class;

use Drupal\file\FileInterface;

/**
 * Interface for bundle classes that have a logo field.
 */
interface LogoInterface {

  /**
   * Returns a renderable array for the logo.
   *
   * @param string|array $display_options
   *   Can be either:
   *   - The name of a view mode. The field will be displayed according to the
   *     display settings specified for this view mode in the $field
   *     definition for the field in the entity's bundle. If no display settings
   *     are found for the view mode, the settings for the 'default' view mode
   *     will be used.
   *   - An array of display options. The following key/value pairs are allowed:
   *     - label: (string) Position of the label. The default 'field' theme
   *       implementation supports the values 'inline', 'above' and 'hidden'.
   *       Defaults to 'above'.
   *     - type: (string) The formatter to use. Defaults to the
   *       'default_formatter' for the field type. The default formatter will
   *       also be used if the requested formatter is not available.
   *     - settings: (array) Settings specific to the formatter. Defaults to the
   *       formatter's default settings.
   *     - weight: (float) The weight to assign to the renderable element.
   *       Defaults to 0.
   *
   * @return array
   *   A renderable array for the logo.
   */
  public function getLogoAsRenderArray($display_options = []): array;

  /**
   * Returns the logo as a file entity.
   *
   * @return \Drupal\file\FileInterface|null
   *   The file entity, or NULL if no logo has been associated with the entity.
   */
  public function getLogoAsFile(): ?FileInterface;

  /**
   * Returns the machine name of the logo field.
   *
   * @return string
   *   The machine name.
   */
  public function getLogoFieldName(): string;

}
