<?php

namespace Drupal\trr\Plugin\Validation\Constraint;

use Drupal\joinup_core\Traits\FieldItemsTrait;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the test resource type field constraint.
 */
class SolutionTestResourceTypeConstraintValidator extends ConstraintValidator {

  use FieldItemsTrait;

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint): void {
    $entity = $items->getEntity();
    /** @var \Drupal\trr\Plugin\Validation\Constraint\SolutionTestResourceTypeConstraint $constraint */
    /** @var \Drupal\Core\Field\FieldItemListInterface $solution_type_field */
    $solution_type_field = $entity->get('field_is_solution_type');

    // If no type is specified, bail out.
    if ($solution_type_field->isEmpty()) {
      return;
    }

    $solution_types = $this->getFieldItemListMainPropertyValues($solution_type_field);
    $current_values = $this->getFieldItemListMainPropertyValues($items);

    if (in_array('http://data.europa.eu/dr8/TestScenario', $solution_types)) {
      if (array_diff($current_values, $this->allowedTestScenarioTypes())) {
        $this->context->addViolation($constraint->invalidTestScenarioMessage);
      }
    }

    $service_or_component = [
      'http://data.europa.eu/dr8/TestComponent',
      'http://data.europa.eu/dr8/TestService',
    ];
    if (array_intersect($service_or_component, $solution_types)) {
      if (array_diff($current_values, $this->allowedServiceAndComponentTypes())) {
        $this->context->addViolation($constraint->invalidTestServiceOrComponentMessage);
      }
    }
  }

  /**
   * The allowed resource types for test scenarios.
   *
   * @return array
   *   The list of allowed values.
   */
  protected function allowedTestScenarioTypes(): array {
    return [
      // Test Suite.
      'http://joinup.eu/test-resource-type#5937f06162191',
      // Test case.
      'http://joinup.eu/test-resource-type#5937f0616252b',
      // Test assertion.
      'http://joinup.eu/test-resource-type#5937f061628ec',
      // Document Assertion Set.
      'http://joinup.eu/test-resource-type#5937f06162c71',
    ];
  }

  /**
   * The allowed resource types for test services and components.
   *
   * @return array
   *   The list of allowed values.
   */
  protected function allowedServiceAndComponentTypes(): array {
    return [
      // Test Bed.
      'http://joinup.eu/test-resource-type#5937f0616111b',
      // Messaging adapter.
      'http://joinup.eu/test-resource-type#5937f06162ff8',
      // Document validator.
      'http://joinup.eu/test-resource-type#5937f0616337f',
    ];
  }

}
