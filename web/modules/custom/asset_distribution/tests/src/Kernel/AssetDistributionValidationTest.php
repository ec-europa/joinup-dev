<?php

declare(strict_types = 1);

namespace Drupal\Tests\asset_distribution\Kernel;

use Drupal\Tests\joinup_core\Kernel\RdfEntityValidationTestBase;

/**
 * Tests the validation on the asset distribution bundle entity.
 *
 * @group entity
 */
class AssetDistributionValidationTest extends RdfEntityValidationTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'asset_distribution',
    'digital_size_formatter',
    'field_group',
    'file',
    'file_url',
    'options',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig('asset_distribution');
  }

  /**
   * {@inheritdoc}
   */
  protected function getRequiredFields(): array {
    return [
      'label',
      'field_ad_licence',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function bundle(): string {
    return 'asset_distribution';
  }

}
