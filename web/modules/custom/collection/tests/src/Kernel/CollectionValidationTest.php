<?php

declare(strict_types = 1);

namespace Drupal\Tests\collection\Kernel;

use Drupal\Tests\joinup_core\Kernel\RdfEntityValidationTestBase;

/**
 * Tests the validation on the collection bundle entity.
 *
 * @group entity_validation
 */
class CollectionValidationTest extends RdfEntityValidationTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'facets',
    'field_group',
    'file',
    'inline_entity_form',
    'options',
    'search_api',
    'search_api_field',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installModule('collection');
    $this->installConfig('collection');
  }

  /**
   * {@inheritdoc}
   */
  protected function getRequiredFields(): array {
    return [
      'label',
      'field_ar_description',
      'field_policy_domain',
      'field_ar_owner',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function bundle(): string {
    return 'collection';
  }

}
