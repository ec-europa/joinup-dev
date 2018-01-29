<?php

declare(strict_types = 1);

namespace Drupal\Tests\solution\Kernel;

use Drupal\Tests\joinup_core\Kernel\RdfEntityValidationTestBase;

/**
 * Tests the validation on the solution bundle entity.
 *
 * @group entity_validation
 */
class SolutionValidationTest extends RdfEntityValidationTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'facets',
    'field_group',
    'file',
    'file_url',
    'inline_entity_form',
    'og',
    'options',
    'search_api',
    'search_api_field',
    'solution',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig('solution');
  }

  /**
   * {@inheritdoc}
   */
  protected function getRequiredFields(): array {
    return [
      'label',
      'field_is_contact_information',
      'field_is_description',
      'field_is_owner',
      'field_is_solution_type',
      'field_policy_domain',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function bundle(): string {
    return 'solution';
  }

}
