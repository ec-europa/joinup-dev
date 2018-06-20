<?php

namespace Drupal\joinup_migrate\EventSubscriber;

use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Drupal\node\Entity\Node;
use Drupal\og\OgGroupAudienceHelperInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * A subscriber adding parent reference to OG menu entries for custom pages.
 */
class CustomPagePostSaveEventSubscriber implements EventSubscriberInterface {

  /**
   * A list of 'ogmenu_instance' entities.
   *
   * @var \Drupal\og_menu\OgMenuInstanceInterface[]
   */
  protected $ogMenuInstances = [];

  /**
   * A list of menu link content entities keyed by the node ID of their target.
   *
   * @var \Drupal\menu_link_content\MenuLinkContentInterface[]
   */
  protected $menuLinks = [];

  /**
   * The 'ogmenu_instance' entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $ogMenuInstanceStorage;

  /**
   * The menu link entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $menuLinkStorage;

  /**
   * Weights within a parent menu.
   *
   * @var int[]
   */
  protected $weight = [];

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [MigrateEvents::POST_ROW_SAVE => 'createParentMenuLink'];
  }

  /**
   * Reacts after a migration row is saved.
   *
   * @param \Drupal\migrate\Event\MigratePostRowSaveEvent $event
   *   The event object.
   */
  public function createParentMenuLink(MigratePostRowSaveEvent $event) {
    if ($event->getMigration()->id() !== 'custom_page') {
      return;
    }

    foreach ($event->getDestinationIdValues() as $nid) {
      $nid = (int) $nid;
      if ($node = Node::load($nid)) {
        $parent_nid = (int) $event->getRow()->getDestinationProperty('parent');
        if (!empty($parent_nid)) {
          if ($parent_link = $this->getMenuLinkByNodeId($parent_nid)) {
            if ($link = $this->getMenuLinkByNodeId($nid)) {
              if (!isset($this->weight[$parent_nid])) {
                $this->weight[$parent_nid] = 0;
              }
              $link->parent->value = "menu_link_content:{$parent_link->uuid()}";
              $link->weight->value = $this->weight[$parent_nid];
              $link->save();
              $this->weight[$parent_nid]++;
            }
          }
        }
      }
    }
  }

  /**
   * Returns an OG menu instance given the OG entity ID.
   *
   * @param string $group_id
   *   The group ID.
   *
   * @return \Drupal\og_menu\OgMenuInstanceInterface
   *   The OG menu instance.
   */
  protected function getOgMenuInstance($group_id) {
    if (!isset($this->ogMenuInstances[$group_id])) {
      if (!isset($this->ogMenuInstanceStorage)) {
        $this->ogMenuInstanceStorage = \Drupal::entityTypeManager()->getStorage('ogmenu_instance');
      }

      $values = [
        'type' => 'navigation',
        OgGroupAudienceHelperInterface::DEFAULT_FIELD => $group_id,
      ];
      $instances = $this->ogMenuInstanceStorage->loadByProperties($values);
      if (!$instances) {
        throw new \RuntimeException("No OG menu for group: $group_id");
      }
      $this->ogMenuInstances[$group_id] = reset($instances);
    }

    return $this->ogMenuInstances[$group_id];
  }

  /**
   * Returns a menu link content entity given a target node ID.
   *
   * @param int $nid
   *   The ID of the node targeted by this menu link.
   *
   * @return \Drupal\menu_link_content\MenuLinkContentInterface|null
   *   The menu link entity or NULL o failure.
   */
  protected function getMenuLinkByNodeId($nid) {
    if (!isset($this->menuLinks[$nid])) {
      $node = Node::load($nid);
      $group_id = $node->get(OgGroupAudienceHelperInterface::DEFAULT_FIELD)->target_id;
      if (!$group_id || (!$instance = $this->getOgMenuInstance($group_id))) {
        return NULL;
      }

      $properties = [
        'menu_name' => 'ogmenu-' . $instance->id(),
        'link__uri' => 'internal:/node/' . $node->id(),
      ];

      if (!isset($this->menuLinkStorage)) {
        $this->menuLinkStorage = \Drupal::entityTypeManager()->getStorage('menu_link_content');
      }

      if (!$links = $this->menuLinkStorage->loadByProperties($properties)) {
        return NULL;
      }
      $this->menuLinks[$nid] = reset($links);
    }

    return $this->menuLinks[$nid];
  }

}
