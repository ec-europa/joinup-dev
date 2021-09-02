<?php

declare(strict_types = 1);

namespace Drupal\joinup\Context;

use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\joinup\Traits\EntityTrait;
use Drupal\joinup\Traits\RdfEntityTrait;
use Drupal\joinup\Traits\SearchTrait;
use Drupal\joinup_collection\JoinupCollectionHelper;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\taxonomy\Entity\Term;

/**
 * Behat step definitions and related methods related to the Joinup collection.
 */
class JoinupCollectionContext extends RawDrupalContext {

  use EntityTrait;
  use SearchTrait;
  use RdfEntityTrait;

  /**
   * Creates the 'Joinup' collection and related data.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown when one of the entities could not be created, for example because
   *   it already exists.
   *
   * @BeforeScenario @joinup&&@api
   */
  public function setupJoinupCollection() {
    // Create the topic term.
    Term::create([
      'vid' => 'topic',
      'tid' => 'http://example.com/term/sna',
      'name' => 'Supra-national authority',
    ])->save();

    // Create the owner.
    Rdf::create([
      'rid' => 'owner',
      'id' => 'http://example.com/owner/isa2',
      'field_owner_name' => 'ISA²',
    ])->save();

    // Create the contact.
    Rdf::create([
      'rid' => 'contact_information',
      'id' => 'http://example.com/contact/joinup',
      'field_contact_name' => 'Contact name',
      'field_contact_email' => 'contact.email@example.com',
    ])->save();

    // Create the Joinup collection.
    Rdf::create([
      'rid' => 'collection',
      'id' => JoinupCollectionHelper::JOINUP_COLLECTION_DEFAULT_ENTITY_ID,
      'label' => 'Joinup',
      'field_ar_state' => 'validated',
      'field_topic' => 'http://example.com/term/sna',
      'field_ar_owner' => 'http://example.com/owner/isa2',
      'field_ar_contact_information' => 'http://example.com/contact/joinup',
    ])->save();
  }

  /**
   * Clears the content created for the purpose of this test.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown when one of the created entities could not be deleted.
   *
   * @AfterScenario @joinup&&@api
   */
  public function cleanJoinupData() {
    $this->disableCommitOnUpdate();

    // Delete the Joinup collection.
    $collection = Rdf::load(JoinupCollectionHelper::JOINUP_COLLECTION_DEFAULT_ENTITY_ID);
    $collection->skip_notification = TRUE;
    $collection->delete();

    // Delete related entities.
    Term::load('http://example.com/term/sna')->delete();
    Rdf::load('http://example.com/owner/isa2')->delete();
    Rdf::load('http://example.com/contact/joinup')->delete();

    $this->enableCommitOnUpdate();
  }

}
