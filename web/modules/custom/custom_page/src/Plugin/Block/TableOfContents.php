<?php

declare(strict_types = 1);

namespace Drupal\custom_page\Plugin\Block;

use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\joinup_core\Plugin\Block\GroupMenuBlock;

/**
 * Table-Of-Contents for top-level custom pages.
 *
 * @Block(
 *   id = "custom_page_toc:navigation",
 *   admin_label = @Translation("Custom page TOC"),
 *   category = @Translation("Custom page"),
 *   context = {
 *     "og" = @ContextDefinition("entity", label = @Translation("Group")),
 *   },
 * ),
 */
class TableOfContents extends GroupMenuBlock {

  /**
   * {@inheritdoc}
   */
  protected function getCurrentRouteMenuTreeParameters(): MenuTreeParameters {
    $parameters = new MenuTreeParameters();

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

    // Get the topmost/root custom page in this hierarchy.
    $trail = $this->menuActiveTrail->getActiveTrailIds($this->getMenuName());
    // Remove the empty root element.
    array_pop($trail);
    // Grab the root page menu link.
    $root_link = array_pop($trail);

    // This block shows only links under the root custom page tree.
    return $parameters->setRoot($root_link);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEmptyResultsBuild(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function addContextualLinks(array &$build): void {}

}
