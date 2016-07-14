<?php

namespace Drupal\rdf_draft\EventSubscriber;

use Drupal\rdf_entity\ActiveGraphEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Load the draft entity on the edit form and on the draft tab.
 */
class ActiveGraphSubscriber implements EventSubscriberInterface {

  /**
   * Sets the active graph to 'draft'.
   *
   * When editing an entity or when viewing it on the draft tab,
   * the graph type is changed to 'draft'.
   *
   * @param \Drupal\rdf_entity\ActiveGraphEvent $event
   *   The Event to process.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Thrown when the access is denied and redirects to user login page.
   */
  public function graphForEntityConvert(ActiveGraphEvent $event) {
    $defaults = $event->getDefaults();
    if ($defaults['_route']) {
      $route_parts = explode('.', $defaults['_route']);
      $last_part = array_pop($route_parts);
      if ($last_part == 'edit_form') {
        $entity_type_id = substr($event->getDefinition()['type'], strlen('entity:'));
        $storage = \Drupal::entityManager()->getStorage($entity_type_id);
        $storage->setActiveGraphType('draft');
        // Draft version already exists.
        if ($storage->load($event->getValue())) {
          $event->setGraph('draft');
        }
        // Use published version to create draft.
        else {
          // Keep track that the entity needs to be stored in the draft graph.
          $storage->setSaveGraph('draft');
          $event->setGraph('default');
        }

      }
      if ($last_part == 'rdf_draft') {
        $event->setGraph('draft');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events['rdf_graph.entity_convert'][] = array('graphForEntityConvert');
    return $events;
  }

}
