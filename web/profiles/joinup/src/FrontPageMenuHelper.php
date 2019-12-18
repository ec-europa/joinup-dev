<?php

namespace Drupal\joinup;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\menu_link_content\Entity\MenuLinkContent as MenuLinkContentEntity;
use Drupal\menu_link_content\Plugin\Menu\MenuLinkContent;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller that assigns to or removes entities from the front page menu.
 */
class FrontPageMenuHelper {

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
   * Builds a new custom page OG menu links updater service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager
   *   The menu link manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MenuLinkManagerInterface $menu_link_manager) {
    $this->menuLinkContentStorage = $entity_type_manager->getStorage('menu_link_content');
    $this->menuLinkManager = $menu_link_manager;
  }

  /**
   * Fetches the menu item content entity for the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to fetch the menu item content entity for.
   *
   * @return \Drupal\menu_link_content\Entity\MenuLinkContent|null
   *   The menu link content interface.
   */
  public function getFrontPageMenuItem(EntityInterface $entity): ?MenuLinkContentEntity {
    if ($entity->isNew()) {
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
   * Adds an entity to the front page menu.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity to add in the front page menu.
   */
  public function pinSiteWide(FieldableEntityInterface $entity): void {
    $this->menuLinkContentStorage->create([
      'title' => $entity->label(),
      'menu_name' => 'front-page',
      'link' => ['uri' => $entity->toUrl()->toUriString()],
      'enabled' => TRUE,
    ])->save();
  }



}
