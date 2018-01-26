<?php

declare(strict_types = 1);

namespace Drupal\Tests\collection\Kernel;

use Drupal\Tests\joinup_core\Kernel\RdfEntityValidationTestBase;

/**
 * Tests the validation on the collection bundle entity.
 *
 * @group entity
 */
class CollectionValidationTest extends RdfEntityValidationTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'file',
    'image',
    'options',
    'link',
    'og',
    'joinup_core',
    'piwik_reporting_api',
    'cached_computed_field',
    'node',
    'taxonomy',
    'rdf_taxonomy',
    'allowed_formats',
    'search_api_field',
    'state_machine',
    'inline_entity_form',
    'field_group',
    'search_api',
    'facets',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig('joinup_core');
    $this->installModule('collection');
    $this->installConfig(['collection']);
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
