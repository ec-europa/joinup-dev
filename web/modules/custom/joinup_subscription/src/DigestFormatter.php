<?php

declare(strict_types = 1);

namespace Drupal\joinup_subscription;

use Drupal\message_digest\DigestFormatter as OriginalFormatter;
use Drupal\user\UserInterface;

/**
 * Extends the message formatter from the message_digest module.
 *
 * The design for digest messages that are sent for collection community content
 * subscriptions requires that the messages are grouped by collection and have a
 * small section inbetween each group that introduces the collection. This class
 * allows to inject these collection introductions in between the messages.
 */
class DigestFormatter extends OriginalFormatter {

  /**
   * The ID of the message template for community content subscription messages.
   *
   * This is used to identify if we are sending a digest for community content
   * subscriptions.
   */
  const TEMPLATE_ID = 'community_content_subscription';

  /**
   * {@inheritdoc}
   */
  public function format(array $digest, array $view_modes, UserInterface $recipient) {
    // This digest formatter is customized for the community content
    // subscription digest. Handle any other digest with the original formatter.
    if (!$this->isCommunityContentSubscriptionDigest($digest)) {
      return parent::format($digest, $view_modes, $recipient);
    }

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

  /**
   * Checks whether the digest is a community content subscription digest.
   *
   * @param array $digest
   *   The array of digest messages.
   *
   * @return bool
   *   TRUE if all of the messages in the digest are community content
   *   subscription messages.
   */
  protected function isCommunityContentSubscriptionDigest(array $digest): bool {
    /** @var \Drupal\message\MessageInterface $message */
    foreach ($digest as $message) {
      if ($message->getTemplate()->id() !== self::TEMPLATE_ID) {
        return FALSE;
      }
    }
    return TRUE;
  }

}
