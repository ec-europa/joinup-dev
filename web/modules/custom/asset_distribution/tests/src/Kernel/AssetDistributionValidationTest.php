<?php

declare(strict_types = 1);

namespace Drupal\Tests\asset_distribution\Kernel;

use Drupal\Core\Serialization\Yaml;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\rdf_entity\Entity\RdfEntityType;
use Drupal\sparql_entity_storage\Entity\SparqlMapping;
use Drupal\Tests\joinup_core\Kernel\RdfEntityValidationTestBase;

/**
 * Tests the validation on the asset distribution bundle entity.
 *
 * @group entity_validation
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
    RdfEntityType::create(['rid' => 'solution'])->save();
    $mapping = Yaml::decode(file_get_contents(__DIR__ . '/../../../../solution/config/install/sparql_entity_storage.mapping.rdf_entity.solution.yml'));
    SparqlMapping::create($mapping)->save();
    $field_storage = Yaml::decode(file_get_contents(__DIR__ . '/../../../../solution/config/install/field.storage.rdf_entity.field_is_distribution.yml'));
    FieldStorageConfig::create($field_storage)->save();
    $field_config = Yaml::decode(file_get_contents(__DIR__ . '/../../../../solution/config/install/field.field.rdf_entity.solution.field_is_distribution.yml'));
    FieldConfig::create($field_config)->save();
    RdfEntityType::create(['rid' => 'asset_release'])->save();
    $mapping = Yaml::decode(file_get_contents(__DIR__ . '/../../../../asset_release/config/install/sparql_entity_storage.mapping.rdf_entity.asset_release.yml'));
    SparqlMapping::create($mapping)->save();
    $field_storage = Yaml::decode(file_get_contents(__DIR__ . '/../../../../asset_release/config/install/field.storage.rdf_entity.field_isr_distribution.yml'));
    FieldStorageConfig::create($field_storage)->save();
    $field_config = Yaml::decode(file_get_contents(__DIR__ . '/../../../../asset_release/config/install/field.field.rdf_entity.asset_release.field_isr_distribution.yml'));
    FieldConfig::create($field_config)->save();
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
