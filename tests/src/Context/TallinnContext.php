<?php

declare(strict_types = 1);

namespace Drupal\joinup\Context;

use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\joinup\Traits\ConfigReadOnlyTrait;
use Drupal\joinup\Traits\EntityTrait;
use Drupal\joinup\Traits\RdfEntityTrait;
use Drupal\joinup\Traits\SearchTrait;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\tallinn\Tallinn;
use Drupal\taxonomy\Entity\Term;

/**
 * Behat step definitions and related methods provided by the tallinn module.
 */
class TallinnContext extends RawDrupalContext {

  use ConfigReadOnlyTrait;
  use EntityTrait;
  use SearchTrait;
  use RdfEntityTrait;

  /**
   * Creates the standard 'Tallinn' collection and several dependencies.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown when one of the entities could not be created, for example because
   *   it already exists.
   *
   * @BeforeScenario @tallinn&&@api
   */
  public function setupTallinnData() {
    // Create two policy domain terms.
    Term::create([
      'vid' => 'policy_domain',
      'tid' => 'http://example.com/term/1',
      'name' => 'Term 1',
    ])->save();
    Term::create([
      'vid' => 'policy_domain',
      'tid' => 'http://example.com/term/2',
      'name' => 'Term 2',
      'parent' => 'http://example.com/term/1',
    ])->save();

    // Create an owner.
    Rdf::create([
      'rid' => 'owner',
      'id' => 'http://example.com/owner',
      'field_owner_name' => 'Owner',
    ])->save();

    Rdf::create([
      'rid' => 'contact_information',
      'id' => 'http://example.com/contact',
      'field_contact_name' => 'Contact name',
      'field_contact_email' => 'contact.email@example.com',
    ])->save();

    // Create the Tallinn entity.
    Rdf::create([
      'rid' => 'collection',
      'id' => Tallinn::TALLINN_COMMUNITY_ID,
      'label' => 'Tallinn Ministerial Declaration',
      'field_ar_state' => 'validated',
      'field_policy_domain' => 'http://example.com/term/2',
      'field_ar_owner' => 'http://example.com/owner',
      'field_ar_contact_information' => 'http://example.com/contact',
    ])->save();

    // The 'Implementation monitoring' standard custom page.
    Node::create([
      'type' => 'custom_page',
      'uuid' => '9d7b6405-061a-4064-ae7e-b34c67f3afad',
      'title' => 'Implementation monitoring',
      'og_audience' => Tallinn::TALLINN_COMMUNITY_ID,
      'field_paragraphs_body' => Paragraph::create([
        'type' => 'simple_paragraph',
        'field_body' => [
          'value' => '{block:tallinn_dashboard}',
          'format' => 'content_editor',
        ],
      ]),
      'field_cp_content_listing' => [
        [
          'value' => [
            'fields' => [
              'field_cp_content_listing_content_type' => [
                'weight' => 0,
                'region' => 'top',
              ],
            ],
            'enabled' => 1,
            'query_presets' => 'entity_bundle|tallinn_report',
            'global_search' => FALSE,
            // Show all tallinn reports in one page.
            'limit' => '33',
          ],
        ],
      ],
    ])->save();
  }

  /**
   * Clears the content created for the purpose of this test.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown when one of the created entities could not be deleted.
   *
   * @AfterScenario @tallinn&&@api
   */
  public function cleanTallinnData() {
    // Temporarily disable the feature to commit the index after every query.
    $this->disableCommitOnUpdate();

    /** @var \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository */
    $entity_repository = \Drupal::service('entity.repository');
    $node = $entity_repository->loadEntityByUuid('node', '9d7b6405-061a-4064-ae7e-b34c67f3afad');
    $node->field_paragraphs_body->entity->delete();
    $node->delete();

    $collection = Rdf::load(Tallinn::TALLINN_COMMUNITY_ID);
    $collection->skip_notification = TRUE;
    $collection->delete();

    // Delete related entities.
    foreach (['http://example.com/term/2', 'http://example.com/term/1'] as $id) {
      Term::load($id)->delete();
    }
    Rdf::load('http://example.com/owner')->delete();
    Rdf::load('http://example.com/contact')->delete();

    // Clear the dashboard access policy state.
    \Drupal::state()->delete('tallinn.access_policy');

    $this->enableCommitOnUpdate();
  }

}
