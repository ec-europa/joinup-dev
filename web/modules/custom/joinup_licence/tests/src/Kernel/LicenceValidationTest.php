<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_licence\Kernel;

use Drupal\Tests\joinup_core\Kernel\RdfEntityValidationTestBase;

/**
 * Tests the validation on the licence bundle entity.
 *
 * @group entity_validation
 */
class LicenceValidationTest extends RdfEntityValidationTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'joinup_licence',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig('joinup_licence');
  }

  /**
   * {@inheritdoc}
   */
  protected function getRequiredFields(): array {
    return [
      'label',
      'field_licence_description',
      'field_licence_type',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function bundle(): string {
    return 'licence';
  }

}
