<?php

namespace Drupal\rdf_entity;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;

/**
 * Render controller for taxonomy terms.
 */
class RdfEntityViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  protected function alterBuild(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode) {

    parent::alterBuild($build, $entity, $display, $view_mode);
    $build['#contextual_links']['rdf_entity'] = array(
      'route_parameters' => array('rdf_entity' => $entity->sanitizedId()),
      'metadata' => array('changed' => $entity->getChangedTime()),
    );
  }

}
