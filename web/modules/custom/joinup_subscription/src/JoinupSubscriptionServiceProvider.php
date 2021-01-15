<?php

declare(strict_types = 1);

namespace Drupal\joinup_subscription;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Replaces the message digest formatter with a customized version.
 *
 * The design for digest messages that are sent for collection content
 * subscriptions requires that the messages are grouped by collection and have a
 * small section inbetween each group that introduces the collection. This class
 * allows to inject these collection introductions in between the messages.
 */
class JoinupSubscriptionServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    if ($container->hasDefinition('message_digest.formatter')) {
      $definition = $container->getDefinition('message_digest.formatter');
      $definition->setClass('Drupal\joinup_subscription\DigestFormatter');
    }
  }

}
