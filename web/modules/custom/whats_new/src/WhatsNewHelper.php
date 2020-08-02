<?php

declare(strict_types = 1);

namespace Drupal\whats_new;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\flag\FlagServiceInterface;
use Drupal\flag\FlaggingInterface;
use Drupal\node\NodeInterface;

/**
 * Helper service for the whats_new module.
 */
class WhatsNewHelper implements WhatsNewHelperInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The flag service.
   *
   * @var \Drupal\flag\FlagServiceInterface
   */
  protected $flagService;

  /**
   * Constructs a WhatsNewHelper object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user object.
   * @param \Drupal\flag\FlagServiceInterface $flag_service
   *   The flag service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountProxyInterface $current_user, FlagServiceInterface $flag_service) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->flagService = $flag_service;
  }

  /**
   * {@inheritdoc}
   */
  public function hasFlagEnabledMenuLinksForEntity(EntityInterface $entity): bool {
    $entity_uris = [
      // Support both the alias and the internal URL. The alias can occur if the
      // user copies it from the URL and the internal URL can occur if the user
      // selects the entity from the autocomplete field.
      $entity->toUrl()->getInternalPath(),
      "entity:node/{$entity->id()}",
    ];
    $query = $this->entityTypeManager->getStorage('menu_link_content')->getQuery()
      ->condition('menu_name', 'support')
      ->condition('link.uri', $entity_uris, 'IN')
      ->condition('enabled', 1)
      ->condition('menu_link_flagging', 1);

    return (bool) $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getFlaggingForNode(NodeInterface $node): ?FlaggingInterface {
    /** @var \Drupal\flag\FlagInterface $flag */
    $flag = $this->entityTypeManager->getStorage('flag')->load('whats_new');
    return $this->flagService->getFlagging($flag, $node, $this->currentUser);
  }

  /**
   * {@inheritdoc}
   */
  public function setFlaggingForNode(NodeInterface $node): void {
    /** @var \Drupal\flag\FlagInterface $flag */
    $flag = $this->entityTypeManager->getStorage('flag')->load('whats_new');
    $this->flagService->flag($flag, $node, $this->currentUser);
  }

}
