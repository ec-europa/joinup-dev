<?php

namespace Drupal\joinup\Traits;

use Behat\Mink\Element\NodeElement;
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
    return $this->findContextualLinksInElement($this->getRegion($region));
  }

  /**
   * Find all the contextual links in an element.
   *
   * Contextual links are retrieved on the browser side through the use
   * of javascript, but that is not applicable for non-javascript browsers.
   *
   * @param \Behat\Mink\Element\NodeElement $element
   *   The name of the element to check.
   *
   * @return array
   *   An array of links found keyed by title.
   */
  protected function findContextualLinksInElement(NodeElement $element) {
    // Since we are calling API functions that depend on the current user, we
    // need to make sure the current user service is up to date. It might still
    // contain the user from a previous API call.
    /** @var \Drupal\DrupalExtension\Context\RawDrupalContext $this */
    $current_user = $this->getUserManager()->getCurrentUser();
    $account = User::load($current_user ? $current_user->uid : 0);
    \Drupal::currentUser()->setAccount($account);

    // Clear the statically cached entities, to ensure that the route parameters
    // will be up to date.
    \Drupal::entityTypeManager()->clearCachedDefinitions();

    /** @var \Drupal\Core\Menu\ContextualLinkManager $contextual_links_manager */
    $contextual_links_manager = \Drupal::service('plugin.manager.menu.contextual_link');

    $client = $this->getSession()->getDriver()->getClient();

    $cloned = clone $client;

    $links = [];
    /** @var \Behat\Mink\Element\NodeElement $item */
    foreach ($element->findAll('xpath', '//*[@data-contextual-id]') as $item) {
      $contextual_id = $item->getAttribute('data-contextual-id');
      $ret = $cloned->request('POST', '/contextual/render', ['ids' => [$contextual_id]]);



      $response = $this->getSession()->getPage()->getContent();



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
