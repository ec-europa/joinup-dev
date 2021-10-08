<?php

declare(strict_types = 1);

namespace Drupal\joinup_test\EventSubscriber;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Joinup test event subscriber.
 */
class JoinupTestSubscriber implements EventSubscriberInterface {

  /**
   * The path matcher service.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * The state manager service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $stateManager;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a JoinupTestSubscriber object.
   *
   * @param \Drupal\Core\Path\PathMatcherInterface $path
   *   The path matcher service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state manager service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(PathMatcherInterface $path, StateInterface $state, MessengerInterface $messenger) {
    $this->pathMatcher = $path;
    $this->stateManager = $state;
    $this->messenger = $messenger;
  }

  /**
   * Kernel request event handler.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   Response event.
   */
  public function onKernelRequest(GetResponseEvent $event): void {
    $state = $this->stateManager->get('joinup_test_messages');
    if (empty($state)) {
      return;
    }

    foreach ($state['messages'] as $type => $messages) {
      foreach ($messages as $message) {
        $this->messenger->addMessage(t($message), $type);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      KernelEvents::REQUEST => ['onKernelRequest'],
    ];
  }

}
