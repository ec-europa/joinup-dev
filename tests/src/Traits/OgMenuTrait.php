<?php

namespace Drupal\joinup\Traits;

use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\og\OgGroupAudienceHelper;
use Drupal\og_menu\Entity\OgMenuInstance;

/**
 * Helper methods when dealing with og menu.
 */
trait OgMenuTrait {

  /**
   * The bundle of the og menu.
   *
   * @var string
   */
  public static $ogMenuInstanceType = 'navigation';

  /**
   * The og menu type id. Used as a prefix for all menu names.
   *
   * @var string
   */
  public static $ogMenuEntityTypeId = 'ogmenu';

  /**
   * Retrieves an og menu instance from the database.
   *
   * Only the navigation bundle is going to be used for the testing due to the
   * fact that this is the default bundle of og_menu.
   *
   * @param string $group_id
   *    The group id of the parent entity.
   *
   * @return mixed|null
   *    The menu instance as retrieved from the database, or NULL if no instance
   *    is found.
   */
  public function getOgMenuInstance($group_id) {
    $values = [
      'type' => self::$ogMenuInstanceType,
      OgGroupAudienceHelper::DEFAULT_FIELD => $group_id,
    ];

    $instances = \Drupal::entityTypeManager()->getStorage('ogmenu_instance')->loadByProperties($values);

    return !empty($instances) ? array_pop($instances) : NULL;
  }

  /**
   * Created an og menu instance for a given group.
   *
   * @param string $group_id
   *    The id of the group that this menu will belong to.
   *
   * @return \Drupal\Core\Entity\EntityInterface|static
   *    The newly created menu instance.
   *
   * @throws \Exception
   *    If the saving was unsuccessful.
   */
  public function createOgMenuInstance($group_id) {
    $values = [
      'type' => self::$ogMenuInstanceType,
      OgGroupAudienceHelper::DEFAULT_FIELD => $group_id,
    ];

    $og_menu_instance = OgMenuInstance::create($values);
    $og_menu_instance->save();
    if ($og_menu_instance->id()) {
      return $og_menu_instance;
    }
    throw new \Exception('Unable to save menu instance.');
  }

  /**
   * Creates a menu link.
   *
   * Used to create menu links for og menu instances.
   * The $item data is an array ready to be passed to the
   * MenuLinkContent::create method.
   *
   * @code
   *
   * $item_data = [
   *  'title' => 'My label for the menu',
   *  'link' => [
   *     'uri' => '/path/of/menu/item',
   *   ],
   *   'menu_name' => menu_machine_name,
   *   'weight' => 1,
   *   'expanded' => TRUE,
   * ];
   *
   * @end_code
   *
   * @param array $item_data
   *    The item data.
   *
   * @see \Drupal\menu_link_content\Entity\MenuLinkContent::create()
   */
  public function createOgMenuItem($item_data) {
    $menu_link = MenuLinkContent::create($item_data);
    $menu_link->save();
  }

}
