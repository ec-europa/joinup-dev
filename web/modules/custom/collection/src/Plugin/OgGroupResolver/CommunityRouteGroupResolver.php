<?php

declare(strict_types = 1);

namespace Drupal\collection\Plugin\OgGroupResolver;

use Drupal\collection\Entity\CommunityInterface;
use Drupal\og\OgResolvedGroupCollectionInterface;
use Drupal\og\Plugin\OgGroupResolver\RouteGroupResolver;

/**
 * Resolves the community from the route.
 *
 * @OgGroupResolver(
 *   id = "collection_from_route",
 *   label = "Community entity from current route",
 *   description = @Translation("Checks if the current route is a community entity path."),
 * )
 */
class CommunityRouteGroupResolver extends RouteGroupResolver {

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
  public function resolve(OgResolvedGroupCollectionInterface $community) {
    if (($group = $this->getContentEntity()) && $group instanceof CommunityInterface) {
      $community->addGroup($group, ['route']);
      $this->stopPropagation();
    }
  }

}
