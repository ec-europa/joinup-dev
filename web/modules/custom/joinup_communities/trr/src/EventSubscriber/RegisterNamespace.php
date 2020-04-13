<?php

declare(strict_types = 1);

namespace Drupal\trr\EventSubscriber;

use EasyRdf\RdfNamespace;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber that registers the TRR namespace on every page load.
 */
class RegisterNamespace implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events['kernel.request'] = ['kernelRequest'];

    return $events;
  }

  /**
   * Register the TRR namespace on each page load.
   *
   * @param \Symfony\Component\EventDispatcher\Event $event
   *   The dispatched event.
   */
  public function kernelRequest(Event $event) {
    RdfNamespace::set('trr', 'http://joinup.ec.europa.eu/trr#');
  }

}
