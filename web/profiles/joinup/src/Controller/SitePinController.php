<?php

namespace Drupal\joinup\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\joinup_core\JoinupRelationManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller to pin/unpin entities site-wide.
 */
class SitePinController extends ControllerBase {

  /**
   * The machine name of the pin field.
   *
   * @var string
   */
  const PINNED_FIELD = 'field_site_pinned';

  /**
   * The Joinup relation manager.
   *
   * @var \Drupal\joinup_core\JoinupRelationManager
   */
  protected $relationManager;

  /**
   * Instantiates a new SitePinController object.
   *
   * @param \Drupal\joinup_core\JoinupRelationManager $relationManager
   *   The Joinup relation manager.
   */
  public function __construct(JoinupRelationManager $relationManager) {
    $this->relationManager = $relationManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('joinup_core.relations_manager')
    );
  }

  /**
   * Pins a content entity site-wide.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity being pinned.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   */
  public function pin(ContentEntityInterface $entity) {
    $entity->set(self::PINNED_FIELD, TRUE)->save();

    drupal_set_message($this->t('@bundle %title has been set as pinned content.', [
      '@bundle' => $entity->get($entity->getEntityType()->getKey('bundle'))->entity->label(),
      '%title' => $entity->label(),
    ]));

    return $this->getRedirect($entity);
  }

  /**
   * Unpins a content entity site-wide.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity being unpinned.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   */
  public function unpin(ContentEntityInterface $entity) {
    $entity->set(self::PINNED_FIELD, FALSE)->save();

    drupal_set_message($this->t('@bundle %title has been removed from the pinned contents.', [
      '@bundle' => $entity->get($entity->getEntityType()->getKey('bundle'))->entity->label(),
      '%title' => $entity->label(),
    ]));

    return $this->getRedirect($entity);
  }

  /**
   * Access check for the pin route.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity being pinned.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function pinAccess(ContentEntityInterface $entity) {
    return AccessResult::allowedIf($entity->hasField(self::PINNED_FIELD) && !$entity->get(self::PINNED_FIELD)->value);
  }

  /**
   * Access check for the unpin route.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity being unpinned.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function unpinAccess(ContentEntityInterface $entity) {
    return AccessResult::allowedIf($entity->hasField(self::PINNED_FIELD) && $entity->get(self::PINNED_FIELD)->value);
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
      $redirect = $this->relationManager->getParent($entity)->toUrl();
    }

    return $this->redirect($redirect->getRouteName(), $redirect->getRouteParameters());
  }

}
