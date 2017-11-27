<?php

namespace Drupal\Tests\joinup_subscription\Kernel;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\flag\Entity\Flag;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;

/**
 * Tests the JoinupSubscription service.
 *
 * @coversDefaultClass \Drupal\joinup_subscription\JoinupSubscription
 *
 * @group joinup
 */
class JoinupSubscriberServiceTest extends KernelTestBase {

  /**
   * A flag entity.
   *
   * @var \Drupal\flag\FlagInterface
   */
  protected $flag;

  /**
   * A list of users used for testing.
   *
   * @var \Drupal\user\UserInterface[]
   */
  protected $users = [];

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

    $this->flag = Flag::create([
      'id' => 'follow',
      'label' => $this->randomString(),
      'entity_type' => 'entity_test',
      'flag_type' => 'entity:entity_test',
    ]);
    $this->flag->save();

    for ($i = 0; $i < 3; $i++) {
      $this->users[$i] = User::create([
        'name' => $name = strtolower($this->randomMachineName()),
        'mail' => "$name@example.com",
        'pass' => user_password(),
      ]);
      $this->users[$i]->save();
    }
  }

  /**
   * Tests subscribers retrieval.
   *
   * @covers ::getSubscribers
   */
  public function testGetSubscribers() {
    $entity = EntityTest::create([
      'name' => $this->randomString(),
      'type' => 'entity_test',
    ]);
    $entity->save();

    /** @var \Drupal\flag\FlagServiceInterface $flag_service */
    $flag_service = $this->container->get('flag');
    for ($i = 0; $i < 3; $i++) {
      $flag_service->flag($this->flag, $entity, $this->users[$i]);
      // We want different subscription creation times in order to check later
      // the subscription order.
      sleep(1);
    }

    /** @var \Drupal\joinup_subscription\JoinupSubscriptionInterface $subscription_service */
    $subscription_service = $this->container->get('joinup_subscription');
    // Get the subscribers.
    $subscribers = $subscription_service->getSubscribers($entity, 'follow');

    // Check that the entity has 3 subscribers.
    $this->assertCount(3, $subscribers);

    // Check subscribed users.
    $this->assertEquals($this->users[0]->id(), (array_shift($subscribers))->id());
    $this->assertEquals($this->users[1]->id(), (array_shift($subscribers))->id());
    $this->assertEquals($this->users[2]->id(), (array_shift($subscribers))->id());
  }

}
