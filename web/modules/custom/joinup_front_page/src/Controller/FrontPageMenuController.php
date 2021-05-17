<?php

declare(strict_types = 1);

namespace Drupal\joinup_front_page\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\joinup_front_page\Entity\PinnableToFrontpageInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller that assigns to or removes entities from the front page menu.
 */
class FrontPageMenuController extends ControllerBase {

  /**
   * Route callback that assigns an entity to the front page menu.
   *
   * @param \Drupal\joinup_front_page\Entity\PinnableToFrontpageInterface $entity
   *   The content entity being pinned.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   */
  public function pinToFrontPage(PinnableToFrontpageInterface $entity): RedirectResponse {
    $entity->pinToFrontPage();

    $this->messenger()->addStatus($this->t('@bundle %title has been set as pinned content.', [
      '@bundle' => $entity->get($entity->getEntityType()->getKey('bundle'))->entity->label(),
      '%title' => $entity->label(),
    ]));
    return $this->getRedirect($entity);
  }

  /**
   * Route callback that removes an entity from the front page menu.
   *
   * @param \Drupal\joinup_front_page\Entity\PinnableToFrontpageInterface $entity
   *   The content entity being unpinned.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   */
  public function unpinFromFrontPage(PinnableToFrontpageInterface $entity): RedirectResponse {
    $entity->unpinFromFrontPage();

    $this->messenger()->addStatus($this->t('@bundle %title has been removed from the pinned contents.', [
      '@bundle' => $entity->get($entity->getEntityType()->getKey('bundle'))->entity->label(),
      '%title' => $entity->label(),
    ]));
    return $this->getRedirect($entity);
  }

  /**
   * Access check for the pin/unpin site wide callbacks.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity being pinned or unpinned.
   * @param bool $value
   *   TRUE if the entity needs to be pinned to the frontpage, FALSE if it needs
   *   to be unpinned.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function routeAccess(ContentEntityInterface $entity, $value): AccessResultInterface {
    if (!$entity instanceof PinnableToFrontpageInterface || $entity->isNew()) {
      return AccessResult::forbidden();
    }

    return AccessResult::allowedIf($value !== $entity->isPinnedToFrontPage());
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
  protected function getRedirect(ContentEntityInterface $entity): RedirectResponse {
    $redirect = $entity->toUrl();
    return $this->redirect($redirect->getRouteName(), $redirect->getRouteParameters());
  }

}
