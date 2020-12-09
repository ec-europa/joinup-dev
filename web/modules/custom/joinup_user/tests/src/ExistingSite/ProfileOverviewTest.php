<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_user\ExistingSite;

use Drupal\Tests\joinup_test\ExistingSite\JoinupExistingSiteTestBase;
use Drupal\Tests\rdf_entity\Traits\DrupalTestTraits\RdfEntityCreationTrait;
use weitzman\LoginTrait\LoginTrait;

/**
 * Tests user cancellation.
 *
 * @group joinup_user
 */
class ProfileOverviewTest extends JoinupExistingSiteTestBase {

  use LoginTrait;
  use RdfEntityCreationTrait;

  /**
   * The owner of the groups.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $owner;

  /**
   * A member of the groups.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $member;

  /**
   * An authenticated user without groups.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $authenticated;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    /** @var \Drupal\og\MembershipManagerInterface $og_membership_manager */
    $og_membership_manager = $this->container->get('og.membership_manager');

    $this->owner = $this->createUser([], 'some_owner', FALSE, [
      'mail' => "some_user@example.com",
      'pass' => 'plain',
      'roles' => [
        // Making the user a moderator only to avoid dealing with the legal
        // agreements.
        'moderator',
      ],
    ]);
    $this->owner->save();
    $this->member = $this->createUser([], 'some_member', FALSE, [
      'mail' => "some_member@example.com",
      'roles' => ['moderator'],
    ]);
    $this->member->save();
    $this->authenticated = $this->createUser([], 'some_user', FALSE, [
      'mail' => "some_user@example.com",
    ]);
    $this->owner->save();

    for ($count = 0; $count < 52; $count++) {
      $collection = $this->createRdfEntity([
        'rid' => 'collection',
        'label' => 'Collection ' . $count,
        'field_ar_state' => 'validated',
        'uid' => $this->owner->id(),
      ]);

      $og_membership_manager->createMembership($collection, $this->member)->save();
      $solution = $this->createRdfEntity([
        'rid' => 'solution',
        'label' => 'Solution ' . $count,
        'collection' => $collection,
        'field_is_state' => 'validated',
        'uid' => $this->owner->id(),
      ]);
      $og_membership_manager->createMembership($solution, $this->member)->save();
    }
  }

  /**
   * Tests the warning message for too many groups.
   */
  public function testTooManyGroupsMessage(): void {
    // Assert a user sees the warning when they are a member of too many groups.
    $this->drupalLogin($this->owner);
    $this->drupalGet("/user/{$this->owner->id()}");
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContainsOnce('You are a member of high number of collections and/or solutions.');

    // Assert the user does not see the same message on another user's profile
    // that is also a member of too many groups.
    $this->drupalGet("/user/{$this->member->id()}");
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('You are a member of high number of collections and/or solutions.');

    // Assert that changing to the member user and visiting their own profile
    // will show the warning (ensure lack of message is not cached).
    $this->drupalLogin($this->member);
    $this->drupalGet("/user/{$this->member->id()}");
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContainsOnce('You are a member of high number of collections and/or solutions.');

    // Assert that the user that is logged in does not see their message in
    // other profiles without many groups.
    $this->drupalGet("/user/{$this->authenticated->id()}");
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('You are a member of high number of collections and/or solutions.');

    // Assert anonymous users cannot see the message in a profile with many
    // groups.
    $this->drupalLogout();
    $this->drupalGet("/user/{$this->owner->id()}");
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('You are a member of high number of collections and/or solutions.');
  }

}
