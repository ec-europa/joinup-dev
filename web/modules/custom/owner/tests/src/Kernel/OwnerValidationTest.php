<?php

declare(strict_types = 1);

namespace Drupal\Tests\owner\Kernel;

use Drupal\Tests\joinup_core\Kernel\RdfEntityValidationTestBase;

/**
 * Tests the validation on the owner bundle entity.
 *
 * @group entity
 */
class OwnerValidationTest extends RdfEntityValidationTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'owner',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig('owner');
  }

  /**
   * {@inheritdoc}
   */
  protected function getRequiredFields(): array {
    return [
      'label',
      'field_owner_name',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function bundle(): string {
    return 'owner';
  }

}
