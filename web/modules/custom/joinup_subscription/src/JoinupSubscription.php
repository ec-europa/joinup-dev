<?php

declare(strict_types = 1);

namespace Drupal\joinup_subscription;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\flag\Entity\Flag;
use Drupal\flag\Entity\Flagging;
use Drupal\user\Entity\User;

/**
 * Provides a service class to handle subscriptions to content.
 */
class JoinupSubscription implements JoinupSubscriptionInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new Joinup subscription service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubscribers(ContentEntityInterface $entity, string $flag_id): array {
    if (!Flag::load($flag_id)) {
      throw new \InvalidArgumentException("Flag with ID '$flag_id' doesn't exist");
    }

    $flaggings = $this->entityTypeManager->getStorage('flagging')->getQuery()
      ->condition('flag_id', $flag_id)
      ->condition('entity_type', $entity->getEntityTypeId())
      ->condition('entity_id', $entity->id())
      ->condition('uid', 0, '>')
      // Sort on subscription date.
      ->sort('created')
      ->execute();

    $subscribers = [];
    if ($flaggings) {
      $uids = [];
      /** @var \Drupal\flag\FlaggingInterface $flagging */
      foreach (Flagging::loadMultiple($flaggings) as $flagging) {
        $uids[] = $flagging->getOwnerId();
      }
      if ($uids) {
        $subscribers = User::loadMultiple($uids);
      }
    }

    return $subscribers;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubscribersAsMails(ContentEntityInterface $entity, string $flag_id): array {
    $mails = [];
    /** @var \Drupal\user\UserInterface $account */
    foreach ($this->getSubscribers($entity, $flag_id) as $uid => $account) {
      $mails[$uid] = $account->getEmail();
    }
    return $mails;
  }

}
