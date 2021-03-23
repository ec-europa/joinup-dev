<?php

declare(strict_types = 1);

namespace Drupal\Tests\spdx\Behat;

use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\spdx\Traits\SpdxContextTrait;

/**
 * Example Behat step definitions for the SPDX module.
 *
 * This class provides step definitions to interact with SPDX licences.
 * Developers are encouraged to use this as an example for creating their own
 * step definitions that are tailored to the business language of their project.
 *
 * For example, a project that internally uses the term "EULA" instead of "SPDX
 * licence" when communicating with business stakeholders can extend this class
 * in their own context and include the following:
 *
 * @code
 * /**
 *  * @Given (the following )EULA:
 *  *\/
 * public function givenSpdxLicences(TableNode $data): void {
 *   return parent::givenSpdxLicences($data);
 * }
 * @endcode
 */
class SpdxContext extends RawDrupalContext {

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
