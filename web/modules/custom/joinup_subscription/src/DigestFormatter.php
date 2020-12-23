<?php

declare(strict_types = 1);

namespace Drupal\joinup_subscription;

use Drupal\joinup_group\Entity\GroupContentInterface;
use Drupal\joinup_group\Entity\GroupInterface;
use Drupal\joinup_group\Exception\MissingGroupException;
use Drupal\joinup_subscription\Entity\GroupContentSubscriptionMessageInterface;
use Drupal\message_digest\DigestFormatter as OriginalFormatter;
use Drupal\user\UserInterface;

/**
 * Extends the message formatter from the message_digest module.
 *
 * The design for digest messages that are sent for group content subscriptions
 * requires that the messages are grouped by the parent group and have a small
 * section in between each group of content that introduces the group. This
 * class allows to inject these group introductions in between the messages.
 */
class DigestFormatter extends OriginalFormatter {

  const DIGEST_TEMPLATE_IDS = [
    'collection' => 'collection_content_subscription',
    'solution' => 'solution_content_subscription',
  ];

  /**
   * {@inheritdoc}
   */
  public function format(array $digest, array $view_modes, UserInterface $recipient) {
    // This digest formatter is customized for the group content subscription
    // digest. Handle any other digest with the original formatter.
    if (!$this->isGroupContentSubscriptionDigest($digest)) {
      return parent::format($digest, $view_modes, $recipient);
    }

    $output = [
      '#theme' => 'message_digest',
      '#messages' => [],
    ];
    $current_group_id = NULL;
    foreach ($digest as $message) {
      // Output a group header if the list of content we're rendering belongs to
      // a new parent group.
      $group = $this->getGroup($message);
      if (empty($group)) {
        // Skip orphaned content. This can happen if the subscribed/ content (or
        // the group to which the content belongs) has been removed in the
        // period since the last digest was sent.
        continue;
      }

      if ($group->id() !== $current_group_id) {
        $current_group_id = $group->id();
        $output[] = $this->entityTypeManager->getViewBuilder('rdf_entity')->view($group, 'digest_message_header');
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
   * Checks whether the digest is a group content subscription digest.
   *
   * @param \Drupal\message\MessageInterface[] $digest
   *   The array of digest messages.
   *
   * @return bool
   *   TRUE if all of the messages in the digest are group content subscription
   *   messages.
   */
  protected function isGroupContentSubscriptionDigest(array $digest): bool {
    foreach ($digest as $message) {
      if (!$message instanceof GroupContentSubscriptionMessageInterface) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Returns the group from the content in the message.
   *
   * @param \Drupal\joinup_subscription\Entity\GroupContentSubscriptionMessageInterface $message
   *   The message that contains the group content for which to return the
   *   group.
   *
   * @return \Drupal\joinup_group\Entity\GroupInterface|null
   *   The group entity, or NULL if the message doesn't have a group or is
   *   orphaned.
   */
  protected function getGroup(GroupContentSubscriptionMessageInterface $message): ?GroupInterface {
    $entity = $message->getSubscribedGroupContent();
    if ($entity instanceof GroupContentInterface) {
      try {
        return $entity->getGroup();
      }
      catch (MissingGroupException $e) {
      }
    }
    return NULL;
  }

}
