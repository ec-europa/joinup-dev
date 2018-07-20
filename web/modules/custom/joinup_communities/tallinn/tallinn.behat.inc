<?php

/**
 * @file
 * Contains \TallinnSubContext.
 */

use Behat\Behat\Hook\Scope\AfterFeatureScope;
use Behat\Behat\Hook\Scope\BeforeFeatureScope;
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
   * Creates the standard 'Tallinn' collection.
   *
   * @param \Behat\Behat\Hook\Scope\BeforeFeatureScope $scope
   *   The scope of the event.
   *
   * @beforeFeature @tallinn
   */
  public static function setupTallinnData(BeforeFeatureScope $scope) {
    // The Tallinn collection might have been created for other purposes.
    if (Rdf::load(TALLINN_COMMUNITY_ID)) {
      throw new \LogicException("The environment is not clean before running tests.");
    }

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
    static::createRdfEntity('owner', [
      'id' => 'http://example.com/owner',
      'field_owner_name' => 'Owner',
    ]);

    // Create the Tallinn entity and the custom page OG menu link.
    static::createRdfEntity('collection', [
      'id' => TALLINN_COMMUNITY_ID,
      'label' => 'Tallinn Ministerial Declaration',
      'field_ar_state' => 'validated',
      'field_policy_domain' => 'http://example.com/term/2',
      'field_ar_owner' => 'http://example.com/owner',
    ]);

    $custom_page = Node::create([
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
    ]);
    $custom_page->save();

    \Drupal::service('custom_page.og_menu_links_manager')->addLink($custom_page);
  }

  /**
   * Clears the collection if it has been created by the test.
   *
   * @param \Behat\Behat\Hook\Scope\AfterFeatureScope $scope
   *   The scope of the event.
   *
   * @afterFeature @tallinn
   */
  public static function cleanTallinnData(AfterFeatureScope $scope) {
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
    \Drupal::state()->delete('tallinn.dashboard.access_policy');
  }

}
