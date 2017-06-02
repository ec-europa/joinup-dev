<?php

namespace Drupal\joinup_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\og\OgAccessInterface;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for routes that are showing lists of group memberships.
 *
 * This is currently used for showing:
 *   - The public member overview on collections, showing user tiles.
 *   - The member administration view that allows facilitators to manage members
 *     using bulk operations.
 *
 * This is inspired by OgAdminMembersController.
 *
 * @see \Drupal\og\Controller\OgAdminMembersController
 */
class GroupMembersController extends ControllerBase {

  /**
   * The OG access service.
   *
   * @var \Drupal\og\OgAccessInterface
   */
  protected $ogAccess;

  /**
   * Constructs a new OgAdminMembersController object.
   *
   * @param \Drupal\og\OgAccessInterface $ogAccess
   *   The OG access service.
   */
  public function __construct(OgAccessInterface $ogAccess) {
    $this->ogAccess = $ogAccess;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('og.access')
    );
  }

  /**
   * Display a list of members that belong to the group.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match service.
   *
   * @return array
   *   The view containing the members overview, as a renderable array.
   */
  public function membersList(RouteMatchInterface $route_match) {
    $group_entity_type_id = $route_match->getRouteObject()->getOption('_og_entity_type_id');
    /** @var \Drupal\Core\Entity\EntityInterface $group */
    $group = $route_match->getParameter($group_entity_type_id);

    // If the user has the permission to manage members (aka is a facilitator),
    // show the administration view. Otherwise, show the public version of the
    // view that shows the memberships as tiles.
    $show_admin_view = $this->ogAccess->userAccess($group, 'manage members')->isAllowed();

    $route_object = $route_match->getRouteObject();
    $route_object->setOption('_admin_route', $show_admin_view);

    $view = $show_admin_view ? 'og_members_overview' : 'members_overview';
    $arguments = [$group->getEntityTypeId(), $group->id()];
    return Views::getView($view)->executeDisplay('default', $arguments);
  }

}
