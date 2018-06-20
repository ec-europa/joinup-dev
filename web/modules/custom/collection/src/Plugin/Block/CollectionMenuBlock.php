<?php

namespace Drupal\collection\Plugin\Block;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Url;
use Drupal\og_menu\OgMenuInstanceInterface;
use Drupal\og_menu\Plugin\Block\OgMenuBlock;

/**
 * Provides the block that displays the menu containing collection pages.
 *
 * @Block(
 *   id = "collection_menu_block",
 *   admin_label = @Translation("Collection menu"),
 *   category = @Translation("Collection"),
 *   deriver = "Drupal\og_menu\Plugin\Derivative\OgMenuBlock",
 *   context = {
 *     "og" = @ContextDefinition("entity", label = @Translation("Collection"))
 *   }
 * )
 */
class CollectionMenuBlock extends OgMenuBlock {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $menu_name = $this->getMenuName();
    $parameters = $this->menuTree->getCurrentRouteMenuTreeParameters($menu_name);

    // Adjust the menu tree parameters based on the block's configuration.
    $level = $this->configuration['level'];
    $depth = $this->configuration['depth'];
    $parameters->setMinDepth($level);
    // When the depth is configured to zero, there is no depth limit. When depth
    // is non-zero, it indicates the number of levels that must be displayed.
    // Hence this is a relative depth that we must convert to an actual
    // (absolute) depth, that may never exceed the maximum depth.
    if ($depth > 0) {
      $parameters->setMaxDepth(min($level + $depth - 1, $this->menuTree->maxDepth()));
    }

    $tree = $this->menuTree->load($menu_name, $parameters);
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    $tree = $this->menuTree->transform($tree, $manipulators);
    $build = $this->menuTree->build($tree);

    // Define URLs that are used in help texts.
    $create_custom_page_url = Url::fromRoute('custom_page.collection_custom_page.add', [
      'rdf_entity' => $this->getContext('og')->getContextData()->getValue()->id(),
    ]);

    $menu_instance = $this->getOgMenuInstance();
    $edit_navigation_menu_url = Url::fromRoute('entity.ogmenu_instance.edit_form', [
      'ogmenu_instance' => $menu_instance->id(),
    ]);

    // If there are entries in the tree but none of those is in the build
    // array, it means that all the available pages have been disabled inside
    // the menu configuration.
    if (empty($build['menu']['#items'])) {
      $build['disabled'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('All the pages have been disabled for this collection. You can <a href=":edit_menu_url">edit the menu configuration</a> or <a href=":add_page_url">add a new page</a>.',
          [
            ':edit_menu_url' => $edit_navigation_menu_url->toString(),
            ':add_page_url' => $create_custom_page_url->toString(),
          ]),
        '#access' => $create_custom_page_url->access(),
      ];
    }

    if ($menu_instance instanceof OgMenuInstanceInterface) {
      // Make sure the cache tag from the OG menu are associated with this
      // block, so that it will always be invalidated whenever the menu changes.
      // @see \Drupal\Core\Menu\MenuTreeStorage::save()
      $build['#cache']['tags'][] = 'config:system.menu.ogmenu-' . $menu_instance->id();

      // Show the "Edit menu" link only when at least one element is available.
      if ($tree) {
        $build['#contextual_links']['ogmenu'] = [
          'route_parameters' => [
            'ogmenu_instance' => $menu_instance->id(),
          ],
        ];
      }
      $build['#contextual_links']['collection_menu_block'] = [
        'route_parameters' => [
          'rdf_entity' => $this->getContext('og')->getContextData()->getValue()->id(),
        ],
      ];
    }

    // Improve the template suggestion.
    if (!empty($build['#items']) && $menu_instance) {
      $menu_name = $menu_instance->getType();
      $build['#theme'] = 'menu__og__' . strtr($menu_name, '-', '_');
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    // Since we are showing a help text to facilitators and owners, this block
    // varies by OG role.
    return Cache::mergeContexts(parent::getCacheContexts(), ['og_role']);
  }

}
