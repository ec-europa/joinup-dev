<?php

declare(strict_types = 1);

namespace Drupal\eif\Plugin\OgGroupResolver;

use Drupal\eif\Eif;
use Drupal\og\OgResolvedGroupCollectionInterface;
use Drupal\og\Plugin\OgGroupResolver\RouteGroupResolver;
use Drupal\rdf_taxonomy\Entity\RdfTerm;

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

  /**
   * {@inheritdoc}
   */
  protected function getContentEntityPaths() {
    return [
      '/rdf_entity/{rdf_entity}/recommendations' => 'rdf_entity',
      '/taxonomy/term/{taxonomy_term}' => 'taxonomy_term',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function resolve(OgResolvedGroupCollectionInterface $collection) {
    if ($entity = $this->getContentEntity()) {
      if ($entity->id() === Eif::EIF_ID || ($entity instanceof RdfTerm && $entity->bundle() === 'eif_recommendation')) {
        /** @var \Drupal\solution\Entity\SolutionInterface $solution */
        if ($solution = $this->entityTypeManager->getStorage('rdf_entity')->load(Eif::EIF_ID)) {
          $collection->addGroup($solution, ['route']);
          // Stop searching for other groups. EIF Toolbox is the only candidate.
          $this->stopPropagation();
        }
      }
    }
  }

}
