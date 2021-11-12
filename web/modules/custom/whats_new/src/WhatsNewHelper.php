<?php

declare(strict_types = 1);

namespace Drupal\whats_new;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\user\UserDataInterface;

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
   * The user data service.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * The cache tag invalidator service.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $invalidator;

  /**
   * The path alias manager service.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * Constructs a WhatsNewHelper object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user object.
   * @param \Drupal\user\UserDataInterface $user_data
   *   The user data service.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $invalidator
   *   The cache tag invalidator service.
   * @param \Drupal\path_alias\AliasManagerInterface $alias_manager
   *   The path alias manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountProxyInterface $current_user, UserDataInterface $user_data, CacheTagsInvalidatorInterface $invalidator, AliasManagerInterface $alias_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->userData = $user_data;
    $this->invalidator = $invalidator;
    $this->aliasManager = $alias_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function menuHasFeaturedLinks(string $menu_name): bool {
    $query = $this->entityTypeManager->getStorage('menu_link_content')->getQuery()
      ->condition('menu_name', $menu_name)
      ->condition('enabled', 1)
      ->condition('live_link', 1);

    return (bool) $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getFlagEnabledMenuLinks(?EntityInterface $entity = NULL): array {
    $query = $this->entityTypeManager->getStorage('menu_link_content')->getQuery()
      ->condition('menu_name', 'support')
      ->condition('enabled', 1)
      ->condition('live_link', 1);

    if ($entity) {
      $system_path = '/' . $entity->toUrl()->getInternalPath();
      $entity_uris = [
        // Support both the alias and the internal URL. The alias can occur if
        // the user copies it from the URL and the internal URL can occur if the
        // user selects the entity from the autocomplete field.
        "internal:{$this->aliasManager->getAliasByPath($system_path)}",
        "entity:node/{$entity->id()}",
      ];

      $query->condition('link.uri', $entity_uris, 'IN');
    }

    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function userHasViewedEntity(EntityInterface $entity): bool {
    $value = $this->userData->get('whats_new', $this->currentUser->id(), 'viewed_entity');
    return !empty($value) && $value === $entity->getEntityTypeId() . ':' . $entity->id();
  }

  /**
   * {@inheritdoc}
   */
  public function setUserHasViewedEntity(EntityInterface $entity): void {
    $this->userData->set('whats_new', $this->currentUser->id(), 'viewed_entity', $entity->getEntityTypeId() . ':' . $entity->id());
    $this->invalidator->invalidateTags($this->entityTypeManager->getStorage('menu')->load('support')->getCacheTagsToInvalidate());
  }

}
