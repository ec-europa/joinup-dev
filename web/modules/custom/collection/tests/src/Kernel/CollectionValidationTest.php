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
    'allowed_formats',
    'cached_computed_field',
    'facets',
    'field_group',
    'file',
    'image',
    'inline_entity_form',
    'joinup_core',
    'link',
    'node',
    'og',
    'options',
    'piwik_reporting_api',
    'rdf_taxonomy',
    'search_api',
    'search_api_field',
    'state_machine',
    'taxonomy',
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
