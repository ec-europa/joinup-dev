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
    $parameters = parent::getCurrentRouteMenuTreeParameters();

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
