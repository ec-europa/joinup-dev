<?php

declare(strict_types = 1);

namespace Drupal\joinup_subscription;

use Drupal\message_digest\DigestFormatter as OriginalFormatter;
use Drupal\user\UserInterface;

/**
 * Extends the message formatter from the message_digest module.
 *
 * The design for digest messages that are sent for collection content
 * subscriptions requires that the messages are grouped by collection and have a
 * small section inbetween each group that introduces the collection. This class
 * allows to inject these collection introductions in between the messages.
 */
class DigestFormatter extends OriginalFormatter {

  /**
   * {@inheritdoc}
   */
  public function format(array $digest, array $view_modes, UserInterface $recipient) {
    $output = [
      '#theme' => 'message_digest',
      '#messages' => [],
    ];
    foreach ($digest as $message) {
      // Set the user to the recipient. This is similar to how message_subscribe
      // works when sending a message to many different users.
      $message->setOwner($recipient);

      $rows = [
        '#theme' => 'message_digest_rows',
        '#message' => $message,
      ];
      foreach ($view_modes as $view_mode) {
        $build = $this->entityTypeManager->getViewBuilder('message')->view($message, $view_mode);
        $rows[] = $build;
      }
      $output[] = $rows;
    }

    return $this->renderer->renderPlain($output);
  }

}
