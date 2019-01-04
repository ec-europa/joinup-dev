<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\Validation\Constraint;

use Drupal\Core\Entity\Plugin\Validation\Constraint\ValidReferenceConstraint;

/**
 * Replaces the core ValidReferenceConstraint class.
 */
class JoinupValidReferenceConstraint extends ValidReferenceConstraint {}
