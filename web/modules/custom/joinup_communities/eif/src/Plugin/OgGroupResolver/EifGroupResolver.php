<?php

declare(strict_types = 1);

namespace Drupal\eif\Plugin\OgGroupResolver;

use Drupal\og\OgResolvedGroupCollectionInterface;
use Drupal\og\Plugin\OgGroupResolver\RouteGroupResolver;
use Drupal\taxonomy\Entity\Term;

/**
 * Resolves the group from the route.
 *
 * Use this to make the EIF Toolbox group available as a route context on the
 * canonical view of the EIF recommendations taxonomy.
 *
 * @OgGroupResolver(
 *   id = "eif_group",
 *   label = "Group entity for the EIF recommendations",
 *   description = @Translation("Sets the EIF Toolbox as a context for the EIF recommendations' canonical path.")
 * )
 */
class EifGroupResolver extends RouteGroupResolver {

  const EIF_ID = 'http://data.europa.eu/w21/405d8980-3f06-4494-b34a-46c388a38651';

  /**
   * {@inheritdoc}
   */
  protected function getContentEntityPaths() {
    return [
      '/taxonomy/term/{taxonomy_term}' => 'taxonomy_term',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function resolve(OgResolvedGroupCollectionInterface $collection) {
    $entity = $this->getContentEntity();
    if (
      $entity
      && ($entity instanceof Term)
      && $entity->bundle() === 'eif_recommendations'
      && $group = $this->entityTypeManager->getStorage('rdf_entity')->load(self::EIF_ID)
    ) {
      $collection->addGroup($group, ['route']);
      // Stop searching for other groups. The EIF Toolbox is the only candidate.
      $this->stopPropagation();
    }

    if (
      $this->routeMatch->getRouteName() === 'view.eif_recommendations.page'
      && $group = $this->entityTypeManager->getStorage('rdf_entity')->load(self::EIF_ID)
    ) {
      $collection->addGroup($group, ['route']);
      // Stop searching for other groups. The EIF Toolbox is the only candidate.
      $this->stopPropagation();
    }
  }

}
