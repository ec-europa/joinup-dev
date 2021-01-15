<?php

declare(strict_types = 1);

namespace Drupal\joinup_group\Plugin\Block;

use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Url;
use Drupal\og_menu\OgMenuInstanceInterface;
use Drupal\og_menu\Plugin\Block\OgMenuBlock;

/**
 * Provides the block that displays the menu containing group pages.
 *
 * @Block(
 *   id = "group_menu_block",
 *   admin_label = @Translation("Group menu"),
 *   category = @Translation("Group"),
 *   deriver = "Drupal\og_menu\Plugin\Derivative\OgMenuBlock",
 *   context = {
 *     "og" = @ContextDefinition("entity", label = @Translation("Group")),
 *   },
 * )
 */
class GroupMenuBlock extends OgMenuBlock {

  /**
   * The OG menu instance.
   *
   * @var \Drupal\og_menu\OgMenuInstanceInterface|null
   */
  protected $ogMenuInstance;

  /**
   * The OG menu tree.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeElement[]
   */
  protected $tree;

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $parameters = $this->getCurrentRouteMenuTreeParameters();
    $tree = $this->menuTree->load($this->getMenuName(), $parameters);
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];

    $menu_instance = $this->getOgMenuInstance();
    $this->tree = $this->menuTree->transform($tree, $manipulators);
    $build = $this->menuTree->build($this->tree);

    if (!empty($build['#items'])) {
      // Improve the template suggestion.
      if ($menu_instance) {
        $menu_name = $menu_instance->getType();
        $build['#theme'] = 'menu__og__' . strtr($menu_name, '-', '_');
      }
    }
    else {
      $build = $this->getEmptyResultsBuild();
    }

    $this->addContextualLinks($build);

    if ($menu_instance) {
      // Make sure the cache tag from the OG menu are associated with this
      // block, so that it will always be invalidated whenever the menu changes.
      // @see \Drupal\Core\Menu\MenuTreeStorage::save()
      $build['#cache']['tags'][] = 'config:system.menu.ogmenu-' . $menu_instance->id();
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(): array {
    // Since we are showing a help text to facilitators and owners, this block
    // varies by OG role.
    return Cache::mergeContexts(parent::getCacheContexts(), ['og_role']);
  }

  /**
   * Prepares and returns the menu tree parameters object.
   *
   * @return \Drupal\Core\Menu\MenuTreeParameters
   *   The menu tree parameters object.
   */
  protected function getCurrentRouteMenuTreeParameters(): MenuTreeParameters {
    $parameters = $this->menuTree->getCurrentRouteMenuTreeParameters($this->getMenuName());
    $this->setMinDepth($parameters)->setMaxDepth($parameters);
    return $parameters;
  }

  /**
   * Sets the min depth for the menu.
   *
   * @param \Drupal\Core\Menu\MenuTreeParameters $parameters
   *   The menu tree parameters object.
   *
   * @return $this
   */
  protected function setMinDepth(MenuTreeParameters $parameters): BlockPluginInterface {
    $parameters->setMinDepth($this->configuration['level']);
    return $this;
  }

  /**
   * Sets the max depth for the menu.
   *
   * @param \Drupal\Core\Menu\MenuTreeParameters $parameters
   *   The menu tree parameters object.
   *
   * @return $this
   */
  protected function setMaxDepth(MenuTreeParameters $parameters): BlockPluginInterface {
    // When the depth is configured to zero, there is no depth limit. When depth
    // is non-zero, it indicates the number of levels that must be displayed.
    // Hence this is a relative depth that we must convert to an actual
    // (absolute) depth, that may never exceed the maximum depth.
    if ($this->configuration['depth'] > 0) {
      $parameters->setMaxDepth(min($this->configuration['level'] + $this->configuration['depth'] - 1, $this->menuTree->maxDepth()));
    }
    return $this;
  }

  /**
   * Returns the markup to be rendered when there are no menu items to show.
   *
   * @return array
   *   A render array for empty menu items.
   */
  protected function getEmptyResultsBuild(): array {
    /** @var \Drupal\rdf_entity\RdfInterface $group */
    $group = $this->getContext('og')->getContextData()->getValue();
    // Define URLs that are used in help texts.
    $create_custom_page_url = Url::fromRoute('joinup_group.add_content', [
      'rdf_entity' => $group->id(),
      'node_type' => 'custom_page',
    ]);
    $edit_navigation_menu_url = Url::fromRoute('entity.ogmenu_instance.edit_form', [
      'ogmenu_instance' => $this->getOgMenuInstance()->id(),
    ]);

    // If there are entries in the tree but none of those is in the build array,
    // it means that all the available pages have been disabled inside the menu
    // configuration.
    $build['disabled'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('All the pages have been disabled for this :type. You can <a href=":edit_menu_url">edit the menu configuration</a> or <a href=":add_page_url">add a new page</a>.',
        [
          ':type' => $group->get('rid')->entity->getSingularLabel(),
          ':edit_menu_url' => $edit_navigation_menu_url->toString(),
          ':add_page_url' => $create_custom_page_url->toString(),
        ]),
      '#access' => $create_custom_page_url->access(),
    ];

    return $build;
  }

  /**
   * Adds contextual links to the block render array.
   *
   * @param array $build
   *   The block render array.
   */
  protected function addContextualLinks(array &$build): void {
    // Show the "Edit menu" link only when at least one element is available.
    if ($this->tree) {
      $build['#contextual_links']['ogmenu'] = [
        'route_parameters' => [
          'ogmenu_instance' => $this->getOgMenuInstance()->id(),
        ],
      ];
    }
    $build['#contextual_links']['group_menu_block'] = [
      'route_parameters' => [
        'rdf_entity' => $this->getContext('og')->getContextData()->getValue()->id(),
        'node_type' => 'custom_page',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getOgMenuInstance(): ?OgMenuInstanceInterface {
    // Wraps the parent method only for caching reasons.
    if (!isset($this->ogMenuInstance)) {
      $this->ogMenuInstance = parent::getOgMenuInstance();
    }
    return $this->ogMenuInstance;
  }

}
