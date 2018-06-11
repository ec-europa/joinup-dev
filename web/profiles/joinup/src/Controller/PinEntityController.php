<?php

namespace Drupal\joinup\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\joinup\JoinupHelper;
use Drupal\joinup\PinServiceInterface;
use Drupal\joinup_core\JoinupRelationManagerInterface;
use Drupal\og\OgAccessInterface;
use Drupal\rdf_entity\RdfInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller to pin/unpin entities inside collections.
 */
class PinEntityController extends ControllerBase {

  /**
   * The OG access service.
   *
   * @var \Drupal\og\OgAccessInterface
   */
  protected $ogAccess;

  /**
   * The pin service.
   *
   * @var \Drupal\joinup\PinServiceInterface
   */
  protected $pinService;

  /**
   * The Joinup relation manager.
   *
   * @var \Drupal\joinup_core\JoinupRelationManagerInterface
   */
  protected $relationManager;

  /**
   * Instantiates a new PinEntityController object.
   *
   * @param \Drupal\joinup_core\JoinupRelationManagerInterface $relationManager
   *   The Joinup relation manager.
   * @param \Drupal\og\OgAccessInterface $ogAccess
   *   The OG access service.
   * @param \Drupal\joinup\PinServiceInterface $pinService
   *   The pin service.
   */
  public function __construct(JoinupRelationManagerInterface $relationManager, OgAccessInterface $ogAccess, PinServiceInterface $pinService) {
    $this->relationManager = $relationManager;
    $this->ogAccess = $ogAccess;
    $this->pinService = $pinService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('joinup_core.relations_manager'),
      $container->get('og.access'),
      $container->get('joinup.pin_service')
    );
  }

  /**
   * Pins a group content entity inside a collection.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity being pinned.
   * @param \Drupal\rdf_entity\RdfInterface $collection
   *   The collection where to pin the content.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   */
  public function pin(ContentEntityInterface $entity, RdfInterface $collection) {
    $this->pinService->setEntityPinned($entity, $collection, TRUE);

    drupal_set_message($this->t('@bundle %title has been pinned in the collection %collection.', [
      '@bundle' => $entity->get($entity->getEntityType()->getKey('bundle'))->entity->label(),
      '%title' => $entity->label(),
      '%collection' => $collection->label(),
    ]));

    return $this->getRedirect($collection);
  }

  /**
   * Unpins a group content entity inside a collection.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity being unpinned.
   * @param \Drupal\rdf_entity\RdfInterface $collection
   *   The collection where to unpin the content.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   */
  public function unpin(ContentEntityInterface $entity, RdfInterface $collection) {
    $this->pinService->setEntityPinned($entity, $collection, FALSE);

    drupal_set_message($this->t('@bundle %title has been unpinned in the collection %collection.', [
      '@bundle' => $entity->get($entity->getEntityType()->getKey('bundle'))->entity->label(),
      '%title' => $entity->label(),
      '%collection' => $collection->label(),
    ]));

    return $this->getRedirect($collection);
  }

  /**
   * Access check for the pin route.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity being pinned.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account to check access for.
   * @param \Drupal\rdf_entity\RdfInterface $collection
   *   The collection where to pin the content.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function pinAccess(ContentEntityInterface $entity, AccountInterface $account, RdfInterface $collection) {
    if (!$this->validEntityParameters($entity, $collection)) {
      return AccessResult::forbidden();
    }

    if (
      !array_key_exists($collection->id(), $this->getCollections($entity)) ||
      $this->pinService->isEntityPinned($entity, $collection)
    ) {
      return AccessResult::forbidden()->addCacheableDependency($collection)->addCacheableDependency($entity);
    }

    return $this->ogAccess->userAccess($collection, 'pin group content', $account);
  }

  /**
   * Access check for the unpin route.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity being unpinned.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account to check access for.
   * @param \Drupal\rdf_entity\RdfInterface $collection
   *   The collection where to pin the content.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function unpinAccess(ContentEntityInterface $entity, AccountInterface $account, RdfInterface $collection) {
    if (!$this->validEntityParameters($entity, $collection)) {
      return AccessResult::forbidden();
    }

    if (
      !array_key_exists($collection->id(), $this->getCollections($entity)) ||
      !$this->pinService->isEntityPinned($entity, $collection)
    ) {
      return AccessResult::forbidden()->addCacheableDependency($collection)->addCacheableDependency($entity);
    }

    return $this->ogAccess->userAccess($collection, 'unpin group content', $account);
  }

  /**
   * Gets the collections an entity is related to.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity.
   *
   * @return \Drupal\rdf_entity\RdfInterface[]
   *   A list of collections the entity is related, keyed by collection id.
   */
  protected function getCollections(ContentEntityInterface $entity) {
    $collections = [];

    if (JoinupHelper::isSolution($entity)) {
      foreach ($entity->get('collection') as $ref) {
        $collections[] = $ref->entity;
      }
      //$collections = $entity->get('collection')->referencedEntities();
    }
    elseif (JoinupHelper::isCommunityContent($entity)) {
      $collections = [$this->relationManager->getParent($entity)];
    }

    $list = [];
    foreach ($collections as $collection) {
      $list[$collection->id()] = $collection;
    }

    return $list;
  }

  /**
   * Validates the types of the entities passed to callbacks.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity that is going to be pinned.
   * @param \Drupal\rdf_entity\RdfInterface $collection
   *   The collection where to pin the entity.
   *
   * @return bool
   *   True if the entities are of the expected types, false otherwise.
   */
  protected function validEntityParameters(ContentEntityInterface $entity, RdfInterface $collection) {
    return (JoinupHelper::isSolution($entity) || JoinupHelper::isCommunityContent($entity)) && JoinupHelper::isCollection($collection);
  }

  /**
   * Returns a response to redirect the user to an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   */
  protected function getRedirect(EntityInterface $entity) {
    $redirect = $entity->toUrl();

    return $this->redirect($redirect->getRouteName(), $redirect->getRouteParameters());
  }

}
