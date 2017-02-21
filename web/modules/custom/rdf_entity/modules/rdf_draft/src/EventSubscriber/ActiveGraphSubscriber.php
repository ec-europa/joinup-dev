<?php

namespace Drupal\rdf_draft\EventSubscriber;

use Drupal\rdf_entity\ActiveGraphEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Load the draft entity on the edit form and on the draft tab.
 */
class ActiveGraphSubscriber implements EventSubscriberInterface {

  /**
   * Set the appropriate graph as an active graph for the entity.
   *
   * Currently, the following cases exist:
   *  - In the canonical view of the entity, load the entity from all graphs. If
   *  a published one exists, then it uses the default behaviour. If a published
   *  one does not exist, then returns the draft version and continues with
   *  proper access check.
   *  - In the edit view, the draft version has priority over the published. If
   *  a draft version exists, then this is the one edited. If a draft version
   *  does not exist, then the published one is cloned into the draft graph.
   *  - The delete view is the same as the canonical view. The published one has
   *  priority over the draft version.
   *  - In any other case, like a 'view draft' tab view, the corresponding graph
   *  is loaded with no fallbacks.
   *
   * @param \Drupal\rdf_entity\ActiveGraphEvent $event
   *   The event object to process.
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
        /** @var RdfEntitySparqlStorage $storage */
        $storage = \Drupal::entityManager()->getStorage($entity_type_id);
        $storage->setRequestGraphs($event->getValue(), ['draft', 'default']);
        $entity = $storage->load($event->getValue());
        // When drafting is enabled for this entity type, try to load the draft
        // version on the edit form.
        if ($this->draftEnabled($entity_type_id, $entity->bundle())) {
          $storage->setRequestGraphs($event->getValue(), ['draft', 'default']);
        }
        else {
          $storage->setRequestGraphs($event->getValue(), ['default']);
        }
        $storage->setRequestGraphs($event->getValue(), ['draft', 'default']);
      }
      // Viewing the entity on a graph specific tab.
      elseif (isset($route_parts[2]) && (strpos($route_parts[2], 'rdf_draft_') === 0)) {
        // Retrieve the graph name from the route.
        $graph_name = str_replace('rdf_draft_', '', $route_parts[2]);
        $event->setGraph($graph_name);
      }
      // On the canonical route, the default entity is preferred.
      elseif (isset($route_parts[2]) && $route_parts[2] === 'canonical') {
        $entity_type_id = substr($event->getDefinition()['type'], strlen('entity:'));
        /** @var RdfEntitySparqlStorage $storage */
        $storage = \Drupal::entityManager()->getStorage($entity_type_id);
        $storage->setRequestGraphs($event->getValue(), ['default', 'draft']);
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

  /**
   * Check if user enabled draft for this bundle.
   *
   * @param string $entity_type_id
   *   Entity type name.
   * @param string $bundle
   *   Bundle name.
   *
   * @return bool
   *   Enabled?
   */
  protected function draftEnabled($entity_type_id, $bundle) {
    $enabled_bundles = \Drupal::config('rdf_draft.settings')->get('revision_bundle_' . $entity_type_id);
    return !empty($enabled_bundles[$bundle]);
  }

  /**
   * Get the graph to use when storing a entity through the create form.
   *
   * @param string $entity_type_id
   *    The entity type id.
   *
   * @return string
   *    The graph to use as default when creating entities.
   */
  protected function defaultSaveGraph($entity_type_id) {
    $default_save_graph = \Drupal::config('rdf_draft.settings')->get('default_save_graph_' . $entity_type_id);
    return !empty($default_save_graph) ? $default_save_graph : 'default';
  }

}
