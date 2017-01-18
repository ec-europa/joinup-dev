<?php

namespace Drupal\Tests\rdf\Kernel;

use Drupal\rdf_entity\Entity\Rdf;
use Drupal\simpletest\UserCreationTrait;
use Drupal\Tests\joinup_core\Kernel\JoinupKernelTestBase;

/**
 * Tests rdf_entity owner functionality.
 *
 * @group rdf_entity
 */
class RdfOwnerTest extends JoinupKernelTestBase {
  use UserCreationTrait;

  /**
   * Tests rdf_entity owner functionality.
   */
  public function testOwner() {
    $owner = $this->createUser();
    $another_owner = $this->createUser();
    $this->setCurrentUser($owner);

    // The 'dummy' bundle does not have the owner mapping. The 'with_owner'
    // does.
    $not_owned = Rdf::create(array(
      'rid' => 'dummy',
      'label' => $this->randomMachineName(),
    ));
    $not_owned->save();
    $owned = Rdf::create(array(
      'rid' => 'with_owner',
      'label' => $this->randomMachineName(),
    ));
    $owned->save();
    $ownerless = Rdf::create(array(
      'rid' => 'with_owner',
      'label' => $this->randomMachineName(),
      'uid' => NULL,
    ));
    $ownerless->save();

    $this->assertNull($not_owned->getOwnerId(), "The entity with no mapping for uid does not have an owner.");
    $this->assertEquals($owner->id(), $owned->getOwnerId(), "The entity with a mapping for uid has an owner.");
    $this->assertNull($ownerless->getOwnerId(), "Entity key 'uid' can be empty.");

    // Verify that even trying to set an owner, no changes are made since no
    // mapping exists.
    $not_owned->setOwner($owner);
    $this->assertNull($not_owned->getOwnerId(), "The entity with no mapping for uid does not set an owner.");

    // Entities with owner should change the value of the owner if another is
    // set.
    $owned->setOwner($another_owner);
    $owned->save();
    $this->assertEquals($another_owner->id(), $owned->getOwnerId(), "The owner is updated properly.");
  }

}
