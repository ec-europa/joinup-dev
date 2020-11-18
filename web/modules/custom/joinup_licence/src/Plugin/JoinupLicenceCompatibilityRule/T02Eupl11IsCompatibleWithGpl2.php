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
 *   id = "T02",
 *   weight = 200,
 * )
 */
class T02Eupl11IsCompatibleWithGpl2 extends JoinupLicenceCompatibilityRulePluginBase {

  const INBOUND_CRITERIA = ['SPDX' => ['GPL-2.0-only', 'GPL-2.0+']];
  const OUTBOUND_CRITERIA = ['SPDX' => ['EUPL-1.1']];

}
