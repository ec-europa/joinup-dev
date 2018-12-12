<?php

declare(strict_types = 1);

namespace Drupal\oe_newsroom_newsletter\NewsletterSubscriber;

use Drupal\Core\State\StateInterface;
use Drupal\oe_newsroom_newsletter\Exception\EmailAddressAlreadySubscribedException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A mocked newsletter subscriber to use in tests.
 *
 * The subscriptions are stored in state.
 */
class MockNewsletterSubscriber implements NewsletterSubscriberInterface {

  /**
   * The key of the state entry that contains the mocked subscriptions.
   */
  const STATE_KEY = 'oe_newsroom_newsletter.mock_subscriptions';

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a new NewsletterSubscriber object.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function subscribe(string $email, string $universe, string $service_id): void {
    $subscriptions = $this->state->get(self::STATE_KEY, []);
    if (!empty($subscriptions[$universe][$service_id][$email])) {
      throw new EmailAddressAlreadySubscribedException();
    }
    $subscriptions[$universe][$service_id][$email] = TRUE;
    $this->state->set(self::STATE_KEY, $subscriptions);
  }

}
