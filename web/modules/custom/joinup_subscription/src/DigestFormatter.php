<?php

declare(strict_types = 1);

namespace Drupal\joinup_subscription;

use Drupal\message\MessageInterface;
use Drupal\message_digest\DigestFormatter as OriginalFormatter;
use Drupal\rdf_entity\RdfInterface;
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
    $current_collection_id = NULL;
    foreach ($digest as $message) {
      // Output a collection header if the community content we're rendering
      // belongs to a new collection.
      $collection = $this->getCollection($message);
      if ($collection->id() !== $current_collection_id) {
        $current_collection_id = $collection->id();
        $output[] = $this->entityTypeManager->getViewBuilder('rdf_entity')->view($collection, 'digest_message_header');
      }

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

  /**
   * Returns the collection related to the community content in the message.
   *
   * @param \Drupal\message\MessageInterface $message
   *   The message that contains the community content for which to return the
   *   collection.
   *
   * @return \Drupal\rdf_entity\RdfInterface
   *   The collection.
   */
  protected function getCollection(MessageInterface $message): RdfInterface {
    // Find the collections by resolving the entity references from the message
    // to the community content to the collection.
    $content = $message->field_community_content->first()->entity;
    return $content->og_audience->first()->entity;
  }

}
