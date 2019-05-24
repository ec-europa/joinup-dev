<?php

namespace Drupal\Tests\asset_release\Kernel;

use Drupal\Core\Serialization\Yaml;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\rdf_entity\Entity\RdfEntityType;
use Drupal\sparql_entity_storage\Entity\SparqlMapping;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\sparql_entity_storage\Traits\SparqlConnectionTrait;

/**
 * Tests field synchronization between solution and release.
 *
 * @group asset_release
 */
class SyncFieldsFromParentSolutionTest extends KernelTestBase {

  use SparqlConnectionTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'allowed_formats',
    'asset_distribution',
    'asset_release',
    'cached_computed_field',
    'comment',
    'contact_information',
    'datetime',
    'ds',
    'facets',
    'field',
    'field_group',
    'file',
    'file_url',
    'image',
    'inline_entity_form',
    'joinup_core',
    'link',
    'matomo_reporting_api',
    'node',
    'oe_newsroom_newsletter',
    'og',
    'options',
    'owner',
    'rdf_schema_field_validation',
    'rdf_draft',
    'rdf_entity',
    'rdf_taxonomy',
    'search_api',
    'search_api_field',
    'smart_trim',
    'solution',
    'sparql_entity_storage',
    'state_machine',
    'system',
    'taxonomy',
    'text',
    'tour',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->setUpSparql();

    $this->installEntitySchema('user');
    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);
    $this->installEntitySchema('rdf_entity');
    $this->installConfig([
      'joinup_core',
      'rdf_draft',
      'rdf_entity',
      'solution',
      'contact_information',
      'owner',
      'asset_release',
      'sparql_entity_storage',
    ]);

    RdfEntityType::create(['rid' => 'collection'])->save();
    $mapping = Yaml::decode(file_get_contents(__DIR__ . '/../../../../collection/config/install/sparql_entity_storage.mapping.rdf_entity.collection.yml'));
    SparqlMapping::create($mapping)->save();
    $field_storage = Yaml::decode(file_get_contents(__DIR__ . '/../../../../collection/config/install/field.storage.rdf_entity.field_ar_affiliates.yml'));
    FieldStorageConfig::create($field_storage)->save();
    $field_config = Yaml::decode(file_get_contents(__DIR__ . '/../../../../collection/config/install/field.field.rdf_entity.collection.field_ar_affiliates.yml'));
    FieldConfig::create($field_config)->save();

    // Create two solution type terms.
    Term::create([
      'vid' => 'eira',
      'tid' => 'http://example.com/eira/t1',
      'name' => 'Type 1',
    ])->save();
    Term::create([
      'vid' => 'eira',
      'tid' => 'http://example.com/eira/t2',
      'name' => 'Type 2',
    ])->save();

    // Create two policy domain terms.
    Term::create([
      'vid' => 'policy_domain',
      'tid' => 'http://example.com/policy-domain/d1',
      'name' => 'Domain 1',
    ])->save();
    Term::create([
      'vid' => 'policy_domain',
      'tid' => 'http://example.com/policy-domain/d2',
      'name' => 'Domain 2',
    ])->save();

    // Create two contact information entities.
    Rdf::create([
      'rid' => 'contact_information',
      'id' => 'http://example.com/contact1',
      'label' => 'Contact 1',
    ])->save();
    Rdf::create([
      'rid' => 'contact_information',
      'id' => 'http://example.com/contact2',
      'label' => 'Contact 2',
    ])->save();

    // Create two owner entities.
    Rdf::create([
      'rid' => 'owner',
      'id' => 'http://example.com/owner1',
      'label' => 'Owner 1',
      'field_owner_state' => 'validated',
    ])->save();
    Rdf::create([
      'rid' => 'owner',
      'id' => 'http://example.com/owner2',
      'label' => 'Owner 2',
      'field_owner_state' => 'validated',
    ])->save();

    // Create two solutions to be related.
    Rdf::create([
      'rid' => 'solution',
      'id' => 'http://example.com/solution-related1',
      'label' => 'Related solution 1',
      'field_is_state' => 'proposed',
    ])->save();
    Rdf::create([
      'rid' => 'solution',
      'id' => 'http://example.com/solution-related2',
      'label' => 'Related solution 2',
      'field_is_state' => 'proposed',
    ])->save();
  }

  /**
   * Tests field synchronization between solution and release.
   */
  public function testSyncFieldsFromParentSolution() {
    // Create a solution.
    Rdf::create([
      'rid' => 'solution',
      'id' => 'http://example.com/solution',
      'label' => 'Solution',
      'field_is_state' => 'validated',
      // Synchronized fields.
      'field_is_description' => 'init description',
      'field_is_solution_type' => 'http://example.com/eira/t1',
      'field_is_contact_information' => 'http://example.com/contact1',
      'field_is_owner' => 'http://example.com/owner1',
      'field_is_related_solutions' => 'http://example.com/solution-related1',
      'field_is_included_asset' => 'http://example.com/solution-related1',
      'field_is_translation' => 'http://example.com/solution-related1',
      'field_policy_domain' => 'http://example.com/policy-domain/d1',
    ])->save();

    // Create a release.
    $release = Rdf::create([
      'rid' => 'asset_release',
      'id' => 'http://example.com/release',
      'label' => 'release',
      'field_isr_is_version_of' => 'http://example.com/solution',
    ]);
    $release->save();

    // Check that the release fields were synchronized.
    $this->assertEquals('init description', $release->field_isr_description->value);
    $this->assertEquals('http://example.com/eira/t1', $release->field_isr_solution_type->target_id);
    $this->assertEquals('http://example.com/contact1', $release->field_isr_contact_information->target_id);
    $this->assertEquals('http://example.com/owner1', $release->field_isr_owner->target_id);
    $this->assertEquals('http://example.com/solution-related1', $release->field_isr_related_solutions->target_id);
    $this->assertEquals('http://example.com/solution-related1', $release->field_isr_included_asset->target_id);
    $this->assertEquals('http://example.com/solution-related1', $release->field_isr_translation->target_id);
    $this->assertEquals('http://example.com/policy-domain/d1', $release->field_policy_domain->target_id);

    // Change the values of solution fields but reload the solution first, in
    // order to get the last value of 'field_is_has_version' field.
    Rdf::load('http://example.com/solution')
      ->set('field_is_description', 'changed description')
      ->set('field_is_solution_type', 'http://example.com/eira/t2')
      ->set('field_is_contact_information', 'http://example.com/contact2')
      ->set('field_is_owner', 'http://example.com/owner2')
      ->set('field_is_related_solutions', 'http://example.com/solution-related2')
      ->set('field_is_included_asset', 'http://example.com/solution-related2')
      ->set('field_is_translation', 'http://example.com/solution-related2')
      ->set('field_policy_domain', 'http://example.com/policy-domain/d2')
      ->save();

    // Reload the release.
    $release = Rdf::load('http://example.com/release');
    // Check that the release fields were synchronized.
    $this->assertEquals('changed description', $release->field_isr_description->value);
    $this->assertEquals('http://example.com/eira/t2', $release->field_isr_solution_type->target_id);
    $this->assertEquals('http://example.com/contact2', $release->field_isr_contact_information->target_id);
    $this->assertEquals('http://example.com/owner2', $release->field_isr_owner->target_id);
    $this->assertEquals('http://example.com/solution-related2', $release->field_isr_related_solutions->target_id);
    $this->assertEquals('http://example.com/solution-related2', $release->field_isr_included_asset->target_id);
    $this->assertEquals('http://example.com/solution-related2', $release->field_isr_translation->target_id);
    $this->assertEquals('http://example.com/policy-domain/d2', $release->field_policy_domain->target_id);
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    // Delete RDF entities.
    $rdf_entity_keys = [
      'contact1',
      'contact2',
      'owner1',
      'owner2',
      'solution-related1',
      'solution-related2',
      'solution',
      'release',
    ];
    foreach ($rdf_entity_keys as $key) {
      if ($rdf = Rdf::load("http://example.com/$key")) {
        $rdf->delete();
      }
    }

    // Delete terms.
    $term_keys = [
      'eira/t1',
      'eira/t2',
      'policy-domain/d1',
      'policy-domain/d2',
    ];
    foreach ($term_keys as $key) {
      if ($term = Term::load("http://example.com/$key")) {
        $term->delete();
      }
    }

    parent::tearDown();
  }

}
