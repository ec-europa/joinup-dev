<?php

declare(strict_types = 1);

namespace Drupal\collection\Plugin\OgGroupResolver;

use Drupal\collection\Entity\CollectionInterface;
use Drupal\og\OgResolvedGroupCollectionInterface;
use Drupal\og\Plugin\OgGroupResolver\RouteGroupResolver;

/**
 * Resolves the collection from the route.
 *
 * @OgGroupResolver(
 *   id = "collection_from_route",
 *   label = "Collection entity from current route",
 *   description = @Translation("Checks if the current route is a collection entity path."),
 * )
 */
class CollectionRouteGroupResolver extends RouteGroupResolver {

  /**
   * {@inheritdoc}
   */
  protected function getContentEntityPaths(): array {
    return [
      '/rdf_entity/{rdf_entity}/glossary/{letter}' => 'rdf_entity',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function resolve(OgResolvedGroupCollectionInterface $collection) {
    if (($group = $this->getContentEntity()) && $group instanceof CollectionInterface) {
      $collection->addGroup($group, ['route']);
      $this->stopPropagation();
    }
  }

}
