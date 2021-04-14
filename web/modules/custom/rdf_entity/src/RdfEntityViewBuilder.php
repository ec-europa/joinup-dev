<?php

declare(strict_types = 1);

namespace Drupal\rdf_entity;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;

/**
 * Render controller for RDF entities.
 */
class RdfEntityViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  protected function alterBuild(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode) {

    parent::alterBuild($build, $entity, $display, $view_mode);
    $build['#contextual_links']['rdf_entity'] = [
      'route_parameters' => ['rdf_entity' => $entity->id()],
      'metadata' => ['changed' => $entity->getChangedTime()],
    ];
  }

}
