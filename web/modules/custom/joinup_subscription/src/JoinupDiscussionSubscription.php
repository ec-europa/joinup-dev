<?php

declare(strict_types = 1);

namespace Drupal\joinup_subscription;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\flag\Entity\Flag;
use Drupal\flag\FlagServiceInterface;
use Drupal\joinup_subscription\Exception\UserAlreadySubscribedException;

/**
 * Provides a service to handle subscriptions to discussions.
 */
class JoinupDiscussionSubscription implements JoinupDiscussionSubscriptionInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The flag service.
   *
   * @var \Drupal\flag\FlagServiceInterface
   */
  protected $flagService;

  /**
   * Constructs a new Joinup subscription service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\flag\FlagServiceInterface $flag_service
   *   The flag service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, FlagServiceInterface $flag_service) {
    $this->entityTypeManager = $entity_type_manager;
    $this->flagService = $flag_service;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubscribers(ContentEntityInterface $entity, string $flag_id): array {
    if (!Flag::load($flag_id)) {
      throw new \InvalidArgumentException("Flag with ID '$flag_id' doesn't exist");
    }

    $flagging_storage = $this->entityTypeManager->getStorage('flagging');
    $flaggings = $flagging_storage->getQuery()
      ->condition('flag_id', $flag_id)
      ->condition('entity_type', $entity->getEntityTypeId())
      ->condition('entity_id', $entity->id())
      ->condition('uid', 0, '>')
      ->execute();

    $subscribers = [];
    if ($flaggings) {
      $uids = [];
      /** @var \Drupal\flag\FlaggingInterface $flagging */
      foreach ($flagging_storage->loadMultiple($flaggings) as $flagging) {
        $uids[] = $flagging->getOwnerId();
      }
      if ($uids) {
        $subscribers = $this->entityTypeManager->getStorage('user')->loadMultiple($uids);

        // Flaggings may be orphaned if the user has been deleted. Filter out
        // any non-existing users.
        $subscribers = array_filter($subscribers);
      }
    }

    return $subscribers;
  }

  /**
   * {@inheritdoc}
   */
  public function subscribe(AccountInterface $account, ContentEntityInterface $entity, string $flag_id): bool {
    assert(!$account->isAnonymous(), 'Only authenticated users can subscribe to discussions.');

    $flag = $this->flagService->getFlagById($flag_id);
    // Throw an exception when the user is already subscribed, so the calling
    // code can generate an appropriate response.
    if ($flag->isFlagged($entity, $account)) {
      $account_name = $account->getAccountName();
      $entity_type = $entity->getEntityTypeId();
      $entity_label = $entity->label();
      throw new UserAlreadySubscribedException("The user '$account_name' is already subscribed to the $entity_type entity with label '$entity_label'.");
    }
    else {
      $flagging = $this->flagService->flag($flag, $entity, $account);
    }

    return !empty($flagging);
  }

  /**
   * {@inheritdoc}
   */
  public function unsubscribe(AccountInterface $account, ContentEntityInterface $entity, string $flag_id): void {
    $flag = $this->flagService->getFlagById($flag_id);
    $this->flagService->unflag($flag, $entity, $account);
  }

  /**
   * {@inheritdoc}
   */
  public function isSubscribed(AccountInterface $account, ContentEntityInterface $entity, string $flag_id): bool {
    $flag = $this->flagService->getFlagById($flag_id);
    return (bool) $this->flagService->getFlagging($flag, $entity, $account);
  }

}
