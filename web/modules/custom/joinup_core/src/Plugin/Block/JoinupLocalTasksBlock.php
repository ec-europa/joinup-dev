<?php

declare(strict_types = 1);

namespace Drupal\joinup_core\Plugin\Block;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Menu\Plugin\Block\LocalTasksBlock;
use Drupal\Core\Render\Element;

/**
 * Provides a "Tabs" block to display the local tasks.
 *
 * Drupal has a mechanism that prevents showing the primary tabs if only one
 * option is allowed. This is based on the idea that the active (current) tab is
 * always visible so if there is only 1 tab active, it is the current one and
 * the user does not need to click to go to the current page.
 *
 * In Joinup, we are hiding off the "View" tab for canonical paths. This can
 * result in cases where there is only 1 tab left but it is not the current one.
 * Thus, we need to override the primary tabs block in order to allow a single
 * tab to be viewed.
 *
 * @Block(
 *   id = "joinup_local_tasks_block",
 *   admin_label = @Translation("Tabs"),
 * )
 */
class JoinupLocalTasksBlock extends LocalTasksBlock {

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $config = $this->configuration;
    $cacheability = new CacheableMetadata();
    $cacheability->addCacheableDependency($this->localTaskManager);
    $tabs = [
      '#theme' => 'menu_local_tasks',
    ];

    // Add only selected levels for the printed output.
    if ($config['primary']) {
      $links = $this->localTaskManager->getLocalTasks($this->routeMatch->getRouteName(), 0);
      $cacheability = $cacheability->merge($links['cacheability']);

      // Do not display single tabs if the only tab points to the same page.
      $visible_children = Element::getVisibleChildren($links['tabs']);
      $cacheability = $cacheability->merge($links['cacheability']);
      $count = count($visible_children);
      $tabs += [
        '#primary' => ($count === 0 || ($count === 1 && key($visible_children) === $this->routeMatch->getRouteName())) ? [] : $links['tabs'],
      ];
    }
    if ($config['secondary']) {
      $links = $this->localTaskManager->getLocalTasks($this->routeMatch->getRouteName(), 1);
      // Do not display single tabs if the only tab points to the same page.
      $visible_children = Element::getVisibleChildren($links['tabs']);
      $cacheability = $cacheability->merge($links['cacheability']);
      $count = count($visible_children);
      // Do not display single tabs if the only tab points to the same page.
      $tabs += [
        '#secondary' => ($count === 0 || ($count === 1 && key($visible_children) === $this->routeMatch->getRouteName())) ? [] : $links['tabs'],
      ];
    }

    $build = [];
    $cacheability->applyTo($build);
    if (empty($tabs['#primary']) && empty($tabs['#secondary'])) {
      return $build;
    }

    return $build + $tabs;
  }

}
