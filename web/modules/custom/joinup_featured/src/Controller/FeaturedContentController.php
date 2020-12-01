<?php

declare(strict_types = 1);

namespace Drupal\joinup_featured\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\joinup_featured\FeaturedContentInterface;
use Drupal\joinup_group\JoinupGroupHelper;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller that allows to mark content as featured / unfeatured.
 */
class FeaturedContentController extends ControllerBase {

  /**
   * Route callback that marks the given entity as featured site wide.
   *
   * @param \Drupal\joinup_featured\FeaturedContentInterface $entity
   *   The content entity to be featured.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   */
  public function feature(FeaturedContentInterface $entity): RedirectResponse {
    $entity->feature();

    $this->messenger()->addMessage($this->t('@bundle %title has been set as featured content.', [
      '@bundle' => $entity->get($entity->getEntityType()->getKey('bundle'))->entity->label(),
      '%title' => $entity->label(),
    ]));

    return $this->getRedirect($entity);
  }

  /**
   * Route callback that removes the given entity from being featured site-wide.
   *
   * @param \Drupal\joinup_featured\FeaturedContentInterface $entity
   *   The content entity to be unfeatured.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   */
  public function unfeature(FeaturedContentInterface $entity): RedirectResponse {
    $entity->unfeature();

    $this->messenger()->addMessage($this->t('@bundle %title has been removed from the featured contents.', [
      '@bundle' => $entity->get($entity->getEntityType()->getKey('bundle'))->entity->label(),
      '%title' => $entity->label(),
    ]));

    return $this->getRedirect($entity);
  }

  /**
   * Checks access for content to be featured.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity being featured.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function featureAccess(ContentEntityInterface $entity): AccessResultInterface {
    return AccessResult::allowedIf($entity instanceof FeaturedContentInterface && !$entity->isFeatured());
  }

  /**
   * Checks access for content to be unfeatured.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity being unfeatured.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function unfeatureAccess(ContentEntityInterface $entity): AccessResultInterface {
    return AccessResult::allowedIf($entity instanceof FeaturedContentInterface && $entity->isFeatured());
  }

  /**
   * Returns a response to redirect the user to the proper page.
   *
   * For nodes, the redirect will be to the collection/solution to which they
   * belong.
   * For collections/solutions, the redirect will be to their canonical page.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity being handled.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response to the node collection.
   */
  protected function getRedirect(ContentEntityInterface $entity) {
    if ($entity->getEntityTypeId() === 'rdf_entity') {
      $redirect = $entity->toUrl();
    }
    else {
      $redirect = JoinupGroupHelper::getGroup($entity)->toUrl();
    }

    return $this->redirect($redirect->getRouteName(), $redirect->getRouteParameters());
  }

}
