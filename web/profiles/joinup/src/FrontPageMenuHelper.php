<?php

namespace Drupal\joinup;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
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
   * {@inheritdoc}
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
   * {@inheritdoc}
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
