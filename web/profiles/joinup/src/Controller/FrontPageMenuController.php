<?php

namespace Drupal\joinup\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\joinup\FrontPageMenuHelper;
use Drupal\menu_link_content\Plugin\Menu\MenuLinkContent;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller that assigns to or removes entities from the front page menu.
 */
class FrontPageMenuController extends ControllerBase {

  /**
   * The front page helper service.
   *
   * @var \Drupal\joinup\FrontPageMenuHelper
   */
  protected $frontPageHelper;

  /**
   * The menu link content storage.
   *
   * @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage
   */
  protected $menuLinkContentStorage;

  /**
   * Builds a new custom page OG menu links updater service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\joinup\FrontPageMenuHelper $front_page_helper
   *   The menu link manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, FrontPageMenuHelper $front_page_helper) {
    $this->menuLinkContentStorage = $entity_type_manager->getStorage('menu_link_content');
    $this->frontPageHelper = $front_page_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('joinup.front_page_helper')
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
  public function pinSiteWide(ContentEntityInterface $entity) {
    $this->frontPageHelper->pinSiteWide($entity);
    $this->entityTypeManager()->getStorage($entity->getEntityTypeId())->resetCache([$entity->id()]);

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
  public function unpinSiteWide(ContentEntityInterface $entity) {
    /** @var \Drupal\menu_link_content\Plugin\Menu\MenuLinkContent $result */
    $this->frontPageHelper->getFrontPageMenuItem($entity)->delete();
    $this->entityTypeManager()->getStorage($entity->getEntityTypeId())->resetCache([$entity->id()]);
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
  public function routeAccess(ContentEntityInterface $entity, $value) {
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
  protected function getRedirect(ContentEntityInterface $entity) {
    $redirect = $entity->toUrl();
    return $this->redirect($redirect->getRouteName(), $redirect->getRouteParameters());
  }

}
