<?php

namespace Drupal\joinup\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Alters existing routes for Joinup specific use cases.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Since all content in Joinup is related to a collection we use custom
    // forms that allow people to add content inside a collection. The standard
    // node / RDF entity forms should not be used for creating new content - the
    // group audience fields are hidden and dangling content would be created
    // that is not associated with any groups.
    // Unfortunately Organic Groups takes over access to the entity routes, and
    // if a user is a collection owner they will be granted access to all create
    // routes.
    // To prevent this we're adding our own access handler to those routes here.
    $routes = [
      'node.add',
      'node.add_page',
      'persistent_login.user_tokens_list',
      'rdf_entity.rdf_add',
      'rdf_entity.rdf_add_page',
      'simplenews.newsletter_subscriptions_user',
      'view.frontpage.feed_1',
      'view.frontpage.page_1',
    ];
    foreach ($routes as $route) {
      if ($route = $collection->get($route)) {
        $route->addRequirements(['_uid_1_only' => 'TRUE']);
      }
    }

    // Override the confirmation form to delete multiple users with our version
    // that prevents deletion of users that are sole owners of collections.
    if ($route = $collection->get('user.multiple_cancel_confirm')) {
      $route->addDefaults([
        '_form' => '\Drupal\joinup\Form\UserMultipleCancelConfirm',
      ]);
    }
  }

}
