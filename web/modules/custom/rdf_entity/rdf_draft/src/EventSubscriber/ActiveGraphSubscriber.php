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
      // On the edit form, load from draft graph if possible.
      if (array_search('edit_form', $route_parts)) {
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
      // On the delete form, select the first available graph.
      elseif (array_search('delete_form', $route_parts)) {
        $entity_type_id = substr($event->getDefinition()['type'], strlen('entity:'));
        $storage = \Drupal::entityManager()->getStorage($entity_type_id);
        $found = FALSE;
        foreach ($storage->getGraphsDefinition() as $name => $definition) {
          if ($found) {
            continue;
          }
          $storage->setActiveGraphType($name);
          if ($storage->load($event->getValue())) {
            $event->setGraph($name);
            $found = TRUE;
          }
        }
      }
      // Viewing the entity on a graph specific tab.
      elseif (isset($route_parts[2]) && (strpos($route_parts[2], 'rdf_draft_') === 0)) {
        // Retrieve the graph name from the route.
        $graph_name = str_replace('rdf_draft_', '', $route_parts[2]);
        $event->setGraph($graph_name);
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
