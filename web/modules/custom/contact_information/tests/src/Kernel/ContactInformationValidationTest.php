<?php

declare(strict_types = 1);

namespace Drupal\Tests\contact_information\Kernel;

use Drupal\Tests\joinup_core\Kernel\RdfEntityValidationTestBase;

/**
 * Tests the validation on the contact information bundle entity.
 *
 * @group entity
 */
class ContactInformationValidationTest extends RdfEntityValidationTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'contact_information',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig('contact_information');
  }

  /**
   * {@inheritdoc}
   */
  protected function getRequiredFields(): array {
    return [
      'label',
      'field_ci_email',
      'field_ci_name',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function bundle(): string {
    return 'contact_information';
  }

}
