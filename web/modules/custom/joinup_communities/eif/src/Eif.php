<?php

declare(strict_types = 1);

namespace Drupal\eif;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\rdf_entity\RdfInterface;

/**
 * A helper class for EIF.
 */
class Eif {

  /**
   * The EIF toolbox solution ID.
   *
   * @var string
   */
  public const EIF_ID = 'http://data.europa.eu/w21/405d8980-3f06-4494-b34a-46c388a38651';

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * Constructs and Eif object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $route_match
   *   The current route match.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, CurrentRouteMatch $route_match) {
    $this->entityTypeManager = $entity_type_manager;
    $this->routeMatch = $route_match;
  }

  /**
   * Checks if the user has access to the recommendations page.
   *
   * The user has access if the page is under the EIF Toolbox solution and the
   * user has view access for the solution.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(): AccessResultInterface {
    $group = $this->routeMatch->getParameter('rdf_entity');
    if (!($group instanceof RdfInterface) || $group->id() !== self::EIF_ID) {
      return AccessResult::neutral();
    }

    return $this->entityTypeManager->getAccessControlHandler('rdf_entity')->access($group, 'view', NULL, TRUE);
  }

}
