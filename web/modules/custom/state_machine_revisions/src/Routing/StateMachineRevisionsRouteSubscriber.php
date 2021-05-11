<?php

declare(strict_types = 1);

namespace Drupal\state_machine_revisions\Routing;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for revisionable entity forms.
 *
 * This is an slightly changed copy of ContentModerationRouteSubscriber. As we
 * don't enable the content_moderation module we cannot benefit of its route
 * subscriber, thus we copy the subscriber in state_machine_revisions module.
 *
 * @see \Drupal\content_moderation\Routing\ContentModerationRouteSubscriber
 */
class StateMachineRevisionsRouteSubscriber extends RouteSubscriberBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Static cache a map of entity types.
   *
   * @var bool[]
   */
  protected $entityTypes;

  /**
   * Constructs a new StateMachineRevisionsRouteSubscriber.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach ($collection as $route) {
      $this->setLatestRevisionFlag($route);
    }
  }

  /**
   * Ensure revisionable entities load the latest revision on entity forms.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route object.
   */
  protected function setLatestRevisionFlag(Route $route) {
    if (!$entity_form = $route->getDefault('_entity_form')) {
      return;
    }
    // Only set the flag on entity types which are revisionable.
    list($entity_type) = explode('.', $entity_form, 2);
    if (!$this->hasStateAndIsRevisionable($entity_type)) {
      return;
    }
    $parameters = $route->getOption('parameters') ?: [];
    foreach ($parameters as &$parameter) {
      if (isset($parameter['type']) && $parameter['type'] === 'entity:' . $entity_type && !isset($parameter['load_latest_revision'])) {
        $parameter['load_latest_revision'] = TRUE;
      }
    }
    $route->setOption('parameters', $parameters);
  }

  /**
   * Checks is an entity type has state and is revisionable.
   *
   * @param string $entity_type_id
   *   The entity type to be checked.
   *
   * @return bool
   *   If the entity type has state and is revisionable.
   */
  protected function hasStateAndIsRevisionable($entity_type_id) {
    if (!isset($this->entityTypes[$entity_type_id])) {
      $has_state_and_is_revisionable = isset($this->entityFieldManager->getFieldMapByFieldType('state')[$entity_type_id]) && $this->entityTypeManager->getDefinition($entity_type_id)->isRevisionable();
      $this->entityTypes[$entity_type_id] = $has_state_and_is_revisionable;
    }
    return $this->entityTypes[$entity_type_id];
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();
    // This needs to run after that EntityResolverManager has set the route
    // entity type.
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -200];
    return $events;
  }

}
