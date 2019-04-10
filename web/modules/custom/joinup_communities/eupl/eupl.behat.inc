<?php

/**
 * @file
 * Contains \EuplSubContext.
 */

use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\joinup\Traits\ConfigReadOnlyTrait;
use Drupal\joinup\Traits\EntityTrait;
use Drupal\joinup\Traits\RdfEntityTrait;
use Drupal\node\Entity\Node;
use Drupal\rdf_entity\Entity\Rdf;

/**
 * Behat step definitions and related methods provided by the eupl module.
 */
class EuplSubContext extends DrupalSubContextBase {

  use ConfigReadOnlyTrait;
  use EntityTrait;
  use RdfEntityTrait;

  /**
   * Creates the standard 'EUPL' collection and several dependencies.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown when one of the entities could not be created, for example because
   *   it already exists.
   *
   * @beforeScenario @eupl
   */
  public function setupEuplData() {
    // Create an owner.
    Rdf::create([
      'rid' => 'owner',
      'id' => 'http://example.com/owner',
      'field_owner_name' => 'Owner',
    ])->save();

    // Create the EUPL entity.
    Rdf::create([
      'rid' => 'collection',
      'id' => EUPL_COMMUNITY_ID,
      'label' => 'EUPL',
      'field_ar_state' => 'validated',
      'field_ar_owner' => 'http://example.com/owner',
    ])->save();

    // The 'Implementation monitoring' standard custom page.
    Node::create([
      'nid' => 701805,
      'type' => 'custom_page',
      'uuid' => '3bee8b04-75fd-46a8-94b3-af0d8f5a4c41',
      'title' => 'JLA',
      'og_audience' => EUPL_COMMUNITY_ID,
    ])->save();
  }

  /**
   * Clears the content created for the purpose of this test.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown when one of the created entities could not be deleted.
   *
   * @afterScenario @eupl
   */
  public function cleanEuplData() {
    /** @var \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository */
    $entity_repository = \Drupal::service('entity.repository');
    $entity_repository->loadEntityByUuid('node', '3bee8b04-75fd-46a8-94b3-af0d8f5a4c41')->delete();

    $collection = Rdf::load(EUPL_COMMUNITY_ID);
    $collection->skip_notification = TRUE;
    $collection->delete();

    Rdf::load('http://example.com/owner')->delete();
  }

}
