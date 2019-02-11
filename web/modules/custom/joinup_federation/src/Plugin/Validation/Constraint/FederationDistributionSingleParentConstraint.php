<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\Validation\Constraint;

use Drupal\asset_distribution\Plugin\Validation\Constraint\DistributionSingleParent;

/**
 * Replaces the DistributionSingleParent class.
 */
class FederationDistributionSingleParentConstraint extends DistributionSingleParent {}
