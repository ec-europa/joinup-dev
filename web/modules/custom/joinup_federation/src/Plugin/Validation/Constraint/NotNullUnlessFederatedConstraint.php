<?php

namespace Drupal\joinup_federation\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraints\NotNull;

/**
 * NotNull constraint.
 *
 * Overrides the Drupal Core validation to handle federated entities.
 */
class NotNullUnlessFederatedConstraint extends NotNull {}
