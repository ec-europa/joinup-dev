<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\Validation\Constraint;

use Drupal\Core\Entity\Plugin\Validation\Constraint\ReferenceAccessConstraint;

/**
 * Replaces the core ReferenceAccessConstraint class.
 */
class JoinupReferenceAccessConstraint extends ReferenceAccessConstraint {}
