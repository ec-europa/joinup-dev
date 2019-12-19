<?php

namespace Drupal\joinup\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\joinup\FrontPageMenuHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller that assigns to or removes entities from the front page menu.
 */
class FrontPageMenuController extends ControllerBase {

  /**
   * The front page helper service.
   *
   * @var \Drupal\joinup\FrontPageMenuHelperInterface
   */
  protected $frontPageHelper;

  /**
   * The menu link content storage.
   *
   * @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage
   */
  protected $menuLinkContentStorage;

  /**
   * The cache tags invalidator service.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidator|\Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * Builds a new custom page OG menu links updater service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\joinup\FrontPageMenuHelperInterface $front_page_helper
   *   The menu link manager service.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator
   *   The cache tags invalidator service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, FrontPageMenuHelperInterface $front_page_helper, CacheTagsInvalidatorInterface $cache_tags_invalidator) {
    $this->menuLinkContentStorage = $entity_type_manager->getStorage('menu_link_content');
    $this->frontPageHelper = $front_page_helper;
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('joinup.front_page_helper'),
      $container->get('cache_tags.invalidator')
    );
  }

  /**
   * Route callback that assigns an entity to the front page menu.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity being processed.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   */
  public function pinSiteWide(ContentEntityInterface $entity): void {
    $this->frontPageHelper->pinSiteWide($entity);
    $this->invalidateEntityTags($entity);

    $this->messenger()->addStatus($this->t('@bundle %title has been set as pinned content.', [
      '@bundle' => $entity->get($entity->getEntityType()->getKey('bundle'))->entity->label(),
      '%title' => $entity->label(),
    ]));
    return $this->getRedirect($entity);
  }

  /**
   * Route callback that removes an entity from the front page menu.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity being processed.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   */
  public function unpinSiteWide(ContentEntityInterface $entity): void {
    /** @var \Drupal\menu_link_content\Plugin\Menu\MenuLinkContent $result */
    $this->frontPageHelper->getFrontPageMenuItem($entity)->delete();
    $this->invalidateEntityTags($entity);

    $this->messenger()->addStatus($this->t('@bundle %title has been removed from the pinned contents.', [
      '@bundle' => $entity->get($entity->getEntityType()->getKey('bundle'))->entity->label(),
      '%title' => $entity->label(),
    ]));
    return $this->getRedirect($entity);
  }

  /**
   * Access check for the pin/unpin site wide callbacks.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity being featured.
   * @param bool $value
   *   The value to set in the field.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function routeAccess(ContentEntityInterface $entity, $value): AccessResultInterface {
    if ($entity->isNew()) {
      return AccessResult::forbidden();
    }

    $menu_item = $this->frontPageHelper->getFrontPageMenuItem($entity);
    $condition = $value ? empty($menu_item) : !empty($menu_item);
    return AccessResult::allowedIf($condition);
  }

  /**
   * Returns a response to redirect the user to the proper page.
   *
   * For nodes, the redirect will be to the collection/solution to which they
   * belong.
   * For collections/solutions, the redirect will be to their canonical page.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity being handled.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response to the node collection.
   */
  protected function getRedirect(ContentEntityInterface $entity): RedirectResponse {
    $redirect = $entity->toUrl();
    return $this->redirect($redirect->getRouteName(), $redirect->getRouteParameters());
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
