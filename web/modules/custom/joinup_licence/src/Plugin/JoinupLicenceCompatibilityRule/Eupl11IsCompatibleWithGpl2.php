<?php

declare(strict_types = 1);

namespace Drupal\joinup_licence\Plugin\JoinupLicenceCompatibilityRule;

use Drupal\joinup_licence\JoinupLicenceCompatibilityRulePluginBase;

/**
 * Implementation of the T02 rule.
 *
 * @codingStandardsIgnoreStart
 * - <Licence-A>: SPDX=GPL-2.0-only OR GPL-2.0+
 * - <Licence-B>: SPDX=EUPL-1.1
 * @codingStandardsIgnoreEnd
 *
 * @JoinupLicenceCompatibilityRule(
 *   id = "eupl_1_1_is_compatible_with_gpl_2",
 *   document_id = "T02",
 *   weight = 200
 * )
 */
class Eupl11IsCompatibleWithGpl2 extends JoinupLicenceCompatibilityRulePluginBase {

  const USE_CRITERIA = ['SPDX' => ['GPL-2.0-only', 'GPL-2.0+']];
  const REDISTRIBUTE_AS_CRITERIA = ['SPDX' => ['EUPL-1.1']];

}
