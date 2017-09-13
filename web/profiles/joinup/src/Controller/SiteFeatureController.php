<?php

namespace Drupal\joinup\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\joinup_core\JoinupRelationManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller to feature/unfeature entities site-wide.
 */
class SiteFeatureController extends ControllerBase {

  /**
   * The machine name of the featured field.
   *
   * @var string
   */
  const FEATURED_FIELD = 'field_site_featured';

  /**
   * The Joinup relation manager.
   *
   * @var \Drupal\joinup_core\JoinupRelationManager
   */
  protected $relationManager;

  /**
   * Instantiates a new SiteFeatureController object.
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
   * Features a content entity site-wide.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity being featured.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   */
  public function feature(ContentEntityInterface $entity) {
    $entity->set(self::FEATURED_FIELD, TRUE)->save();

    drupal_set_message($this->t('@bundle %title has been set as featured content.', [
      '@bundle' => $entity->get($entity->getEntityType()->getKey('bundle'))->entity->label(),
      '%title' => $entity->label(),
    ]));

    return $this->getRedirect($entity);
  }

  /**
   * Unfeatures a content entity site-wide.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity being unfeatured.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   */
  public function unfeature(ContentEntityInterface $entity) {
    $entity->set(self::FEATURED_FIELD, FALSE)->save();

    drupal_set_message($this->t('@bundle %title has been removed from the feature contents.', [
      '@bundle' => $entity->get($entity->getEntityType()->getKey('bundle'))->entity->label(),
      '%title' => $entity->label(),
    ]));

    return $this->getRedirect($entity);
  }

  /**
   * Access check for the feature route.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity being featured.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function featureAccess(ContentEntityInterface $entity) {
    return AccessResult::allowedIf($entity->hasField(self::FEATURED_FIELD) && !$entity->get(self::FEATURED_FIELD)->value);
  }

  /**
   * Access check for the unfeature route.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity being unfeatured.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function unfeatureAccess(ContentEntityInterface $entity) {
    return AccessResult::allowedIf($entity->hasField(self::FEATURED_FIELD) && $entity->get(self::FEATURED_FIELD)->value);
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
