<?php

/**
 * @file
 * Contains \SpdxSubContext.
 */

declare(strict_types = 1);

use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\spdx\Traits\SpdxContextTrait;

/**
 * Behat step definitions for the SPDX Licences module.
 */
class SpdxSubContext extends DrupalSubContextBase {

  use SpdxContextTrait;

  /**
   * Mapping of human readable field labels to machine names.
   */
  protected const FIELD_ALIASES = [
    'uri' => 'id',
    'ID' => 'field_spdx_licence_id',
    'title' => 'label',
    'see also' => 'field_spdx_see_also',
    'text' => 'field_spdx_licence_text',
  ];

}
