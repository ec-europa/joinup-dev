<?php

declare(strict_types = 1);

namespace Drupal\Tests\asset_release\Kernel;

use Drupal\Tests\joinup_core\Kernel\RdfEntityValidationTestBase;

/**
 * Tests the validation on the asset release bundle entity.
 *
 * @group entity
 */
class AssetReleaseValidationTest extends RdfEntityValidationTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'file',
    'file_url',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installModule('asset_release');
    $this->installConfig('asset_release');
  }

  /**
   * {@inheritdoc}
   */
  protected function getRequiredFields(): array {
    return [
      'label',
      'field_isr_description',
      'field_isr_logo',
      'field_isr_owner',
      'field_policy_domain',
      'field_isr_release_number',
      'field_isr_banner',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function bundle(): string {
    return 'asset_release';
  }

}
