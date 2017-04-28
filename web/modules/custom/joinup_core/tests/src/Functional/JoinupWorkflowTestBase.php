<?php

namespace Drupal\Tests\joinup_core\Functional;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\Entity\OgMembership;
use Drupal\Tests\rdf_entity\Functional\RdfWebTestBase;

/**
 * Base setup for a Joinup workflow test.
 *
 * @group rdf_entity
 */
abstract class JoinupWorkflowTestBase extends RdfWebTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'joinup';

  /**
   * The og membership access manager service.
   *
   * @var \Drupal\og\OgAccess
   */
  protected $ogAccess;

  /**
   * The og membership manager service.
   *
   * @var \Drupal\og\MembershipManager
   */
  protected $ogMembershipManager;

  /**
   * The entity access manager service.
   *
   * @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  protected $entityAccess;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->ogMembershipManager = \Drupal::service('og.membership_manager');
    $this->ogAccess = $this->container->get('og.access');
    $this->entityAccess = $this->container->get('entity_type.manager')->getAccessControlHandler($this->getEntityType());
  }

  /**
   * Creates a user with roles.
   *
   * @param array $roles
   *   An array of roles to initialize the user with.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The created user object.
   */
  protected function createUserWithRoles(array $roles = []) {
    $user = $this->createUser();
    foreach ($roles as $role) {
      $user->addRole($role);
    }
    $user->save();

    return $user;
  }

  /**
   * Creates and asserts an Og membership.
   *
   * @param \Drupal\Core\Entity\EntityInterface $group
   *   The Og group.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user this membership refers to.
   * @param array $roles
   *   An array of role objects.
   */
  protected function createOgMembership(EntityInterface $group, AccountInterface $user, array $roles = []) {
    $membership = $this->ogMembershipManager->createMembership($group, $user)->setRoles($roles);
    $membership->save();
    $loaded = $this->ogMembershipManager->getMembership($group, $user);
    $this->assertInstanceOf(OgMembership::class, $loaded, t('A membership was successfully created.'));
  }

  /**
   * Returns the type of the entity being tested.
   *
   * @return string
   *   The entity type.
   */
  abstract protected function getEntityType();

  /**
   * Returns the entity bundle for the tested node type.
   *
   * @return string
   *   The entity bundle machine name.
   */
  abstract protected function getEntityBundle();

}
