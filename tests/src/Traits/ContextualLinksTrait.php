<?php

namespace Drupal\joinup\Traits;

use Drupal\Core\Url;
use Drupal\user\Entity\User;

/**
 * Helper methods to deal with contextual links.
 */
trait ContextualLinksTrait {

  /**
   * Find all the contextual links in a region, without the need for javascript.
   *
   * @param string $region
   *   The name of the region.
   *
   * @return array
   *   An array of links found keyed by title.
   *
   * @throws \Exception
   *   When the region is not found in the page.
   */
  protected function findContextualLinksInRegion($region) {
    // Since we are calling API functions that depend on the current user, we
    // need to make sure the current user service is up to date. It might still
    // contain the user from a previous API call.
    /** @var \Drupal\DrupalExtension\Context\RawDrupalContext $this */
    $account = User::load($this->getUserManager()->getCurrentUser()->uid);
    \Drupal::currentUser()->setAccount($account);

    $links = [];

    /** @var \Drupal\Core\Menu\ContextualLinkManager $contextual_links_manager */
    $contextual_links_manager = \Drupal::service('plugin.manager.menu.contextual_link');
    $regionObj = $this->getRegion($region);

    /** @var \Behat\Mink\Element\NodeElement $item */
    foreach ($regionObj->findAll('xpath', '//*[@data-contextual-id]') as $item) {
      $contextual_id = $item->getAttribute('data-contextual-id');
      foreach (_contextual_id_to_links($contextual_id) as $group_name => $link) {
        $route_parameters = $link['route_parameters'];
        foreach ($contextual_links_manager->getContextualLinkPluginsByGroup($group_name) as $plugin_id => $plugin_definition) {
          /** @var \Drupal\Core\Menu\ContextualLinkInterface $plugin */
          $plugin = $contextual_links_manager->createInstance($plugin_id);
          $route_name = $plugin->getRouteName();
          // Check access.
          if (!\Drupal::accessManager()->checkNamedRoute($route_name, $route_parameters, $account)) {
            continue;
          }
          /** @var \Drupal\Core\Url $url */
          $url = Url::fromRoute($route_name, $route_parameters, $plugin->getOptions())->toRenderArray();
          $links[$plugin->getTitle()] = $url['#url']->toString();
        }
      }
    }

    return $links;
  }

}
