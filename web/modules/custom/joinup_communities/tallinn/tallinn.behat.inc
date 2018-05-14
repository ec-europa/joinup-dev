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
use Drupal\rdf_entity\Entity\Rdf;

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
      return;
    }

    // Create the Tallinn entity and the custom page OG menu link.
    static::createRdfEntity('collection', [
      'id' => TALLINN_COMMUNITY_ID,
      'label' => 'Tallinn Ministerial Declaration',
      'field_ar_state' => 'validated',
    ]);
    \Drupal::state()->set('tallinn_testing_collection', TRUE);
    /** @var \Drupal\node\NodeInterface $custom_page */
    $custom_page = static::getEntityByLabel('node', 'Tallinn initiative', 'custom_page');
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
    $state = \Drupal::state();
    if ($state->get('tallinn_testing_collection')) {
      $state->delete('tallinn_testing_collection');
      $collection = Rdf::load(TALLINN_COMMUNITY_ID);
      $collection->skip_notification = TRUE;

      // Save OG orphans policy.
      $og_config = \Drupal::configFactory()->getEditable('og.settings');
      $delete_orphans = $og_config->get('delete_orphans');
      // Keep group content.
      static::bypassReadOnlyConfig();
      $og_config->set('delete_orphans', FALSE)->save();
      // Delete collection.
      $collection->delete();
      // Restore OG policy.
      $og_config->set('delete_orphans', $delete_orphans)->save();
      static::restoreReadOnlyConfig();
    }
  }

}
