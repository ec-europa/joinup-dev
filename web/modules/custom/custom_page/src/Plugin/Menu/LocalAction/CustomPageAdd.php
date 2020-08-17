<?php

declare(strict_types = 1);

namespace Drupal\custom_page\Plugin\Menu\LocalAction;

use Drupal\Core\Menu\LocalActionDefault;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * Provides the group entity for the 'Add custom page' local action.
 */
class CustomPageAdd extends LocalActionDefault {

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters(RouteMatchInterface $route_match) {
    $parameters = parent::getRouteParameters($route_match);

    // In order to add a custom page we need to know with which collection or
    // solution this page will be associated, so we do not get any dangling
    // pages. This local action is shown on the navigation menu edit form, so we
    // can retrieve the collection or solution from the menu instance.
    if (empty($parameters['rdf_entity'])) {
      /** @var \Drupal\og_menu\Entity\OgMenuInstance $instance */
      /** @var \Drupal\Core\Entity\ContentEntityInterface $group */
      if (($instance = $route_match->getParameter('ogmenu_instance')) && ($group = $instance->getGroup()) && $group->getEntityTypeId() === 'rdf_entity') {
        $parameters[$group->getEntityTypeId()] = $group->id();
      }
      else {
        throw new ResourceNotFoundException();
      }
    }
    if (empty($parameters['node_type'])) {
      $parameters['node_type'] = 'custom_page';
    }

    return $parameters;
  }

}
