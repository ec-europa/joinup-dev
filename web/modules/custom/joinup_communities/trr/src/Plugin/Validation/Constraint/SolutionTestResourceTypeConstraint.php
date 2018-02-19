<?php

declare(strict_types = 1);

namespace Drupal\trr\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Specific constraint for the solution test resource type field.
 *
 * @Constraint(
 *   id = "SolutionTestResourceType",
 *   label = @Translation("Specific constraint for the solution test resource type field.", context = "Validation"),
 * )
 */
class SolutionTestResourceTypeConstraint extends Constraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = 'Invalid value specified for the "Test resource type" field.';

  /**
   * The violation message when a wrong type is specified for test scenarios.
   *
   * @var string
   */
  public $invalidTestScenarioMessage = 'Test resource type should be either "Test Suite", "Test Case", "Test Assertion" or "Document Assertion Set" when solution type is set to "Test scenario".';

  /**
   * The violation message shown on wrong types for test services or components.
   *
   * @var string
   */
  public $invalidTestServiceOrComponentMessage = 'Test resource type should be either "Test Bed", "Messaging Adapter" or "Document Validator" when solution type is set to "Test service" or "Test component".';

}
