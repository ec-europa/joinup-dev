<?php

namespace Drupal\Tests\joinup_subscription\Kernel;

use Drupal\Core\Session\AccountInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\flag\Entity\Flag;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests the JoinupSubscription service.
 *
 * @coversDefaultClass \Drupal\joinup_subscription\JoinupSubscription
 *
 * @group joinup
 */
class JoinupSubscriberServiceTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * A flag entity.
   *
   * @var \Drupal\flag\FlagInterface
   */
  protected $flag;

  /**
   * A test content entity that the test users will subscribe to.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected $entity;

  /**
   * A list of users used for testing.
   *
   * @var \Drupal\user\UserInterface[]
   */
  protected $users = [];

  /**
   * A test flag ID.
   *
   * @var string
   */
  protected $testFlagId = 'follow';

  /**
   * The subscription service. This is the system under test.
   *
   * @var \Drupal\joinup_subscription\JoinupSubscriptionInterface
   */
  protected $subscriptionService;

  /**
   * The flag service.
   *
   * @var \Drupal\flag\FlagServiceInterface
   */
  protected $flagService;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity_test',
    'flag',
    'joinup_subscription',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', ['sequences']);
    $this->installSchema('flag', ['flag_counts']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('flagging');

    $this->subscriptionService = $this->container->get('joinup_subscription.subscription');
    $this->flagService = $this->container->get('flag');

    $this->flag = Flag::create([
      'id' => $this->testFlagId,
      'label' => $this->randomString(),
      'entity_type' => 'entity_test',
      'flag_type' => 'entity:entity_test',
    ]);
    $this->flag->save();

    $this->entity = EntityTest::create([
      'name' => $this->randomString(),
      'type' => 'entity_test',
    ]);
    $this->entity->save();
  }

  /**
   * Tests subscribers retrieval.
   *
   * @covers ::getSubscribers
   */
  public function testGetSubscribers() {
    // Create some subscribed test users.
    $users = $this->createSubscribedUsers();

    // Check that the subscribed users are returned.
    $this->assertSubscribers($users);
  }

  /**
   * Tests subscribing a user.
   *
   * @covers ::subscribe
   */
  public function testSubscribe() {
    $users = [];
    for ($i = 0; $i < 3; $i++) {
      $users[$i] = $this->createUser();
      $this->subscriptionService->subscribe($users[$i], $this->entity, $this->testFlagId);
      $this->assertSubscribers($users);
    }
  }

  /**
   * Tests subscribing a user.
   *
   * @covers ::unsubscribe
   */
  public function testUnsubscribe() {
    $users = $this->createSubscribedUsers();
    foreach ($users as $key => $user) {
      $this->subscriptionService->unsubscribe($user, $this->entity, $this->testFlagId);
      unset($users[$key]);
      $this->assertSubscribers($users);
    }
  }

  /**
   * Tests checking if a user is subscribed.
   */
  public function testIsSubscribed() {
    $subscribed_users = $this->createSubscribedUsers(1);
    $subscribed_user = reset($subscribed_users);
    $unsubscribed_user = $this->createUser();

    \PHPUnit_Framework_Assert::assertTrue($this->subscriptionService->isSubscribed($subscribed_user, $this->entity, $this->testFlagId));
    \PHPUnit_Framework_Assert::assertFalse($this->subscriptionService->isSubscribed($unsubscribed_user, $this->entity, $this->testFlagId));
  }

  /**
   * Creates a number of test users and subscribes them using the Flag service.
   *
   * @param int $count
   *   The number of test users to create.
   *
   * @return \Drupal\Core\Session\AccountInterface[]
   *   The subscribed users.
   */
  protected function createSubscribedUsers(int $count = 3) : array {
    $users = [];
    for ($i = 0; $i < $count; $i++) {
      $users[$i] = $this->createUser();
      $this->flagService->flag($this->flag, $this->entity, $users[$i]);
    }
    return $users;
  }

  /**
   * Checks that the subscription service returns the correct subscribers.
   *
   * @param \Drupal\Core\Session\AccountInterface[] $expected_subscribers
   *   The subscribers that are expected to be present.
   */
  protected function assertSubscribers(array $expected_subscribers) : void {
    $actual_subscribers = $this->subscriptionService->getSubscribers($this->entity, $this->testFlagId);
    foreach ($expected_subscribers as $expected_subscriber) {
      // Check that an entry with the subscriber ID is present in the list.
      $id = $expected_subscriber->id();
      \PHPUnit_Framework_Assert::assertArrayHasKey($id, $actual_subscribers);

      // Check that the entry is a user account and that it is the right one.
      $actual_subscriber = $actual_subscribers[$id];
      \PHPUnit_Framework_Assert::assertInstanceOf(AccountInterface::class, $actual_subscriber);
      \PHPUnit_Framework_Assert::assertEquals($expected_subscriber->id(), $actual_subscriber->id());

      unset($actual_subscribers[$id]);
    }

    // Check that no unexpected subscribers are present.
    \PHPUnit_Framework_Assert::assertEmpty($actual_subscribers);
  }

}
