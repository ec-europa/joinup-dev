<?php

namespace Drupal\asset_distribution\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if a distribution is linked to single solution or release parent.
 *
 * @Constraint(
 *   id = "DistributionSingleParent",
 *   label = @Translation("Distribution single parent constraint", context = "Validation"),
 * )
 */
class DistributionSingleParent extends Constraint {

  /**
   * The message to show when the constraint is not met.
   *
   * @var string
   */
  public $message = "The distribution %label is linked also by the %parent @bundle.";

}
