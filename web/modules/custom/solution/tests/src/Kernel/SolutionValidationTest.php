<?php

declare(strict_types = 1);

namespace Drupal\Tests\solution\Kernel;

use Drupal\Core\Serialization\Yaml;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\rdf_entity\Entity\RdfEntityType;
use Drupal\sparql_entity_storage\Entity\SparqlMapping;
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
    'asset_distribution',
    'facets',
    'field_group',
    'file',
    'file_url',
    'inline_entity_form',
    'og',
    'options',
    'search_api',
    'search_api_field',
    'smart_trim',
    'solution',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig('solution');
    RdfEntityType::create(['rid' => 'collection'])->save();
    $mapping = Yaml::decode(file_get_contents(__DIR__ . '/../../../../collection/config/install/sparql_entity_storage.mapping.rdf_entity.collection.yml'));
    SparqlMapping::create($mapping)->save();
    $field_storage = Yaml::decode(file_get_contents(__DIR__ . '/../../../../collection/config/install/field.storage.rdf_entity.field_ar_affiliates.yml'));
    FieldStorageConfig::create($field_storage)->save();
    $field_config = Yaml::decode(file_get_contents(__DIR__ . '/../../../../collection/config/install/field.field.rdf_entity.collection.field_ar_affiliates.yml'));
    FieldConfig::create($field_config)->save();
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
