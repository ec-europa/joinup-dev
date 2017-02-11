<?php

namespace Drupal\joinup\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Session\AccountInterface;

/**
 * Controls access to routes according to Joinup specific business rules.
 */
class JoinupCustomAccess implements AccessInterface {

  /**
   * Controls access to routes according to Joinup specific business rules.
   *
   * @param \Drupal\Core\Routing\RouteMatch $route_match
   *   The current route match service.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in user.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(RouteMatch $route_match, AccountInterface $account) {
    // UID 1 has access to all routes.
    if ($account->id() == 1) {
      return AccessResult::allowed()->addCacheContexts(['user']);
    }

    switch ($route_match->getRouteName()) {
      case 'rdf_entity.rdf_add':
        $bundle = $route_match->getRawParameter('rdf_type');
        // Moderators have temporary access to /rdf_entity/add/{rdf_type} for
        // all RDF entities that do not yet have a custom controller for
        // creating them. These custom controllers currently exist for
        // collections, solutions and licenses. For all other types we currently
        // allow access to the default entity create form to facilitate user
        // acceptance testing. This will be removed once we have custom
        // controllers and links in the dashboard or the plus button menu.
        // Non-moderators never have access to the default entity create forms,
        // they can only create RDF entities through the custom controllers via
        // the plus button.
        $temporary_allowed_bundles = [
          'asset_distribution',
          'asset_release',
          'contact_information',
          'owner',
        ];
        if (in_array('moderator', $account->getRoles()) && in_array($bundle, $temporary_allowed_bundles)) {
          return AccessResult::allowed()->addCacheContexts(['user']);
        }
        break;
    }

    // Access was not granted by the business rules, default to no access.
    return AccessResult::forbidden()->addCacheContexts(['user']);
  }

}
