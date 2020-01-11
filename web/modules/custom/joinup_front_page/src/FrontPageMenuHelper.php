<?php

declare(strict_types = 1);

namespace Drupal\joinup_front_page;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\menu_link_content\Entity\MenuLinkContent as MenuLinkContentEntity;

/**
 * Controller that assigns to or removes entities from the front page menu.
 */
class FrontPageMenuHelper implements FrontPageMenuHelperInterface {

  /**
   * The menu link manager service.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menuLinkManager;

  /**
   * The menu link content storage.
   *
   * @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage
   */
  protected $menuLinkContentStorage;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The cache tags invalidator service.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * Builds a new custom page OG menu links updater service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager
   *   The menu link manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator
   *   The cache tags invalidator service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MenuLinkManagerInterface $menu_link_manager, ModuleHandlerInterface $module_handler, CacheTagsInvalidatorInterface $cache_tags_invalidator) {
    $this->menuLinkContentStorage = $entity_type_manager->getStorage('menu_link_content');
    $this->menuLinkManager = $menu_link_manager;
    $this->moduleHandler = $module_handler;
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
  }

  /**
   * {@inheritdoc}
   */
  public function getFrontPageMenuItem(EntityInterface $entity): ?MenuLinkContentEntity {
    if ($entity->isNew() || empty($entity->id())) {
      return NULL;
    }

    $menu_items = $this->menuLinkContentStorage->loadByProperties([
      'bundle' => 'menu_link_content',
      'menu_name' => 'front-page',
      'link__uri' => $entity->toUrl()->toUriString(),
    ]);
    return empty($menu_items) ? NULL : reset($menu_items);
  }

  /**
   * {@inheritdoc}
   */
  public function pinSiteWide(FieldableEntityInterface $entity): void {
    $this->menuLinkContentStorage->create([
      'title' => $entity->label(),
      'menu_name' => 'front-page',
      'link' => ['uri' => $entity->toUrl()->toUriString()],
      'enabled' => TRUE,
    ])->save();
    $this->invalidateEntityTags($entity);
    // Check the existence of the `search_api` module in order to relax the
    // dependency chain since the `search_api` part is secondary functionality
    // here.
    if ($this->moduleHandler->moduleExists('search_api')) {
      $entity->original = $entity;
      search_api_entity_update($entity);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function unpinSiteWide(FieldableEntityInterface $entity): void {
    $this->getFrontPageMenuItem($entity)->delete();
    $this->invalidateEntityTags($entity);
    if ($this->moduleHandler->moduleExists('search_api')) {
      $entity->original = $entity;
      search_api_entity_update($entity);
    }
  }

  /**
   * Helper method to gather and invalidate tags for an entity.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity to invalidate tags for.
   */
  protected function invalidateEntityTags(FieldableEntityInterface $entity): void {
    $cache_tags_to_invalidate = Cache::mergeTags($entity->getEntityType()->getListCacheTags(), $entity->getCacheTagsToInvalidate());
    $this->cacheTagsInvalidator->invalidateTags($cache_tags_to_invalidate);
  }

}
