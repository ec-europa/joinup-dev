<?php

declare(strict_types = 1);

namespace Drupal\Tests\asset_release\ExistingSite;

use Drupal\Tests\joinup_test\ExistingSite\JoinupExistingSiteTestBase;
use Drupal\Tests\rdf_entity\Traits\DrupalTestTraits\RdfEntityCreationTrait;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Tests field synchronization between solution and release.
 *
 * @group asset_release
 */
class SyncFieldsFromParentSolutionTest extends JoinupExistingSiteTestBase {

  use RdfEntityCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    /** @var \Drupal\taxonomy\VocabularyInterface $vocabulary */
    $vocabulary = Vocabulary::load('eira');

    // Create two solution type terms.
    $this->createTerm($vocabulary, [
      'tid' => 'http://example.com/eira/t1',
      'name' => 'Type 1',
    ]);
    $this->createTerm($vocabulary, [
      'tid' => 'http://example.com/eira/t2',
      'name' => 'Type 2',
    ]);

    // Create two policy domain terms.
    $vocabulary = Vocabulary::load('policy_domain');
    $this->createTerm($vocabulary, [
      'tid' => 'http://example.com/policy-domain/d1',
      'name' => 'Domain 1',
    ]);
    $this->createTerm($vocabulary, [
      'tid' => 'http://example.com/policy-domain/d2',
      'name' => 'Domain 2',
    ]);

    // Create two contact information entities.
    $this->createRdfEntity([
      'rid' => 'contact_information',
      'id' => 'http://example.com/contact1',
      'label' => 'Contact 1',
    ]);
    $this->createRdfEntity([
      'rid' => 'contact_information',
      'id' => 'http://example.com/contact2',
      'label' => 'Contact 2',
    ]);

    // Create two owner entities.
    $this->createRdfEntity([
      'rid' => 'owner',
      'id' => 'http://example.com/owner1',
      'label' => 'Owner 1',
      'field_owner_state' => 'validated',
    ]);
    $this->createRdfEntity([
      'rid' => 'owner',
      'id' => 'http://example.com/owner2',
      'label' => 'Owner 2',
      'field_owner_state' => 'validated',
    ]);

    $this->createRdfEntity([
      'rid' => 'collection',
      'id' => 'http://example.com/collection',
      'field_ar_state' => 'validated',
      'label' => 'Collection',
    ]);

    // Create two solutions to be related.
    $this->createRdfEntity([
      'rid' => 'solution',
      'id' => 'http://example.com/solution-related1',
      'label' => 'Related solution 1',
      'field_is_state' => 'proposed',
      'collection' => 'http://example.com/collection',
    ]);
    $this->createRdfEntity([
      'rid' => 'solution',
      'id' => 'http://example.com/solution-related2',
      'label' => 'Related solution 2',
      'field_is_state' => 'proposed',
      'collection' => 'http://example.com/collection',
    ]);
  }

  /**
   * Tests field synchronization between solution and release.
   */
  public function testSyncFieldsFromParentSolution() {
    // Create a solution.
    $this->createRdfEntity([
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
      'collection' => 'http://example.com/collection',
    ]);

    // Create a release.
    $release = $this->createRdfEntity([
      'rid' => 'asset_release',
      'id' => 'http://example.com/release',
      'label' => 'release',
      'field_isr_is_version_of' => 'http://example.com/solution',
    ]);

    // Check that the release fields were synchronized.
    $this->assertSame('init description', $release->field_isr_description->value);
    $this->assertSame('http://example.com/eira/t1', $release->field_isr_solution_type->target_id);
    $this->assertSame('http://example.com/contact1', $release->field_isr_contact_information->target_id);
    $this->assertSame('http://example.com/owner1', $release->field_isr_owner->target_id);
    $this->assertSame('http://example.com/solution-related1', $release->field_isr_related_solutions->target_id);
    $this->assertSame('http://example.com/solution-related1', $release->field_isr_included_asset->target_id);
    $this->assertSame('http://example.com/solution-related1', $release->field_isr_translation->target_id);
    $this->assertSame('http://example.com/policy-domain/d1', $release->field_policy_domain->target_id);

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
    $this->assertSame('changed description', $release->field_isr_description->value);
    $this->assertSame('http://example.com/eira/t2', $release->field_isr_solution_type->target_id);
    $this->assertSame('http://example.com/contact2', $release->field_isr_contact_information->target_id);
    $this->assertSame('http://example.com/owner2', $release->field_isr_owner->target_id);
    $this->assertSame('http://example.com/solution-related2', $release->field_isr_related_solutions->target_id);
    $this->assertSame('http://example.com/solution-related2', $release->field_isr_included_asset->target_id);
    $this->assertSame('http://example.com/solution-related2', $release->field_isr_translation->target_id);
    $this->assertSame('http://example.com/policy-domain/d2', $release->field_policy_domain->target_id);
  }

}
