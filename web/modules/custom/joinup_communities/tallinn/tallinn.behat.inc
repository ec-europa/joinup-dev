<?php

/**
 * @file
 * Contains \TallinnSubContext.
 */

use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\joinup\Traits\ConfigReadOnlyTrait;
use Drupal\joinup\Traits\EntityTrait;
use Drupal\joinup\Traits\RdfEntityTrait;
use Drupal\node\Entity\Node;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\taxonomy\Entity\Term;

/**
 * Behat step definitions and related methods provided by the tallinn module.
 */
class TallinnSubContext extends DrupalSubContextBase {

  use ConfigReadOnlyTrait;
  use EntityTrait;
  use RdfEntityTrait;

  /**
   * Creates the standard 'Tallinn' collection and several dependencies.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown when one of the entities could not be created, for example because
   *   it already exists.
   *
   * @beforeScenario @tallinn
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

    // Create the Tallinn entity.
    Rdf::create([
      'rid' => 'collection',
      'id' => TALLINN_COMMUNITY_ID,
      'label' => 'Tallinn Ministerial Declaration',
      'field_ar_state' => 'validated',
      'field_policy_domain' => 'http://example.com/term/2',
      'field_ar_owner' => 'http://example.com/owner',
    ])->save();

    // The 'Implementation monitoring' standard custom page.
    Node::create([
      'type' => 'custom_page',
      'uuid' => '9d7b6405-061a-4064-ae7e-b34c67f3afad',
      'title' => 'Implementation monitoring',
      'og_audience' => TALLINN_COMMUNITY_ID,
      'body' => [
        [
          'value' => '{block:tallinn_dashboard}',
          'format' => 'content_editor',
        ],
      ],
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
   * @afterScenario @tallinn
   */
  public function cleanTallinnData() {
    /** @var \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository */
    $entity_repository = \Drupal::service('entity.repository');
    $entity_repository->loadEntityByUuid('node', '9d7b6405-061a-4064-ae7e-b34c67f3afad')->delete();

    $collection = Rdf::load(TALLINN_COMMUNITY_ID);
    $collection->skip_notification = TRUE;
    $collection->delete();

    // Delete related entities.
    foreach (['http://example.com/term/2', 'http://example.com/term/1'] as $id) {
      Term::load($id)->delete();
    }
    Rdf::load('http://example.com/owner')->delete();

    // Clear the dashboard access policy state.
    \Drupal::state()->delete('tallinn.dashboard.access_policy');
  }

}
