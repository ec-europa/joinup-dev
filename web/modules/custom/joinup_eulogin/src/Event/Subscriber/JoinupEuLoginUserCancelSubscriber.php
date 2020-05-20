<?php

declare(strict_types = 1);

namespace Drupal\joinup_eulogin\Event\Subscriber;

use Drupal\externalauth\AuthmapInterface;
use Drupal\joinup_user\Event\JoinupUserCancelEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Reacts on user cancelling.
 */
class JoinupEuLoginUserCancelSubscriber implements EventSubscriberInterface {

  /**
   * The authmap service.
   *
   * @var \Drupal\externalauth\AuthmapInterface
   */
  protected $authmap;

  /**
   * Constructs a new event subscriber instance.
   *
   * @param \Drupal\externalauth\AuthmapInterface $authmap
   *   The authmap service.
   */
  public function __construct(AuthmapInterface $authmap) {
    $this->authmap = $authmap;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      'joinup_user.cancel' => 'onUserCancel',
    ];
  }

  /**
   * Removes the CAS linkage when a user is cancelled.
   *
   * @param \Drupal\joinup_user\Event\JoinupUserCancelEvent $event
   *   The user cancel event.
   */
  public function onUserCancel(JoinupUserCancelEvent $event): void {
    $this->authmap->delete($event->getAccount()->id(), 'cas');
  }

}
