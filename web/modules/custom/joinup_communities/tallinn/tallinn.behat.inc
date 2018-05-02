<?php

/**
 * @file
 * Contains \TallinnSubContext.
 */

use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\joinup\Traits\EntityTrait;
use Drupal\joinup\Traits\RdfEntityTrait;
use Drupal\rdf_entity\Entity\Rdf;

/**
 * Behat step definitions and related methods provided by the tallinn module.
 */
class TallinnSubContext extends DrupalSubContextBase {

  use EntityTrait;
  use RdfEntityTrait;

  /**
   * Creates the standard 'Tallinn' collection.
   *
   * @beforeScenario @tallinn
   */
  public function setupTallinnData() {
    // The Tallinn collection might have been created for other purposes.
    if (Rdf::load(TALLINN_COMMUNITY_ID)) {
      return;
    }

    // Create the Tallinn entity and the custom page OG menu link.
    $this->createRdfEntity('collection', [
      'id' => TALLINN_COMMUNITY_ID,
      'label' => 'Tallinn Ministerial Declaration',
      'field_ar_state' => 'validated',
    ]);
    /** @var \Drupal\node\NodeInterface $custom_page */
    $custom_page = $this->getEntityByLabel('node', 'Tallinn initiative', 'custom_page');
    \Drupal::service('custom_page.og_menu_links_manager')->addLink($custom_page);
  }

}
