<?php

declare(strict_types = 1);

namespace Drupal\joinup_subscription;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\flag\Entity\Flag;
use Drupal\flag\Entity\Flagging;
use Drupal\flag\FlagServiceInterface;
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

    $flaggings = $this->entityTypeManager->getStorage('flagging')->getQuery()
      ->condition('flag_id', $flag_id)
      ->condition('entity_type', $entity->getEntityTypeId())
      ->condition('entity_id', $entity->id())
      ->condition('uid', 0, '>')
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
  public function subscribe(AccountInterface $account, ContentEntityInterface $entity, string $flag_id): bool {
    $flag = $this->flagService->getFlagById($flag_id);
    $flagging = $this->flagService->flag($flag, $entity, $account);

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
