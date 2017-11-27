<?php

namespace Drupal\joinup\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\joinup\JoinupHelper;
use Drupal\joinup\PinServiceInterface;
use Drupal\joinup_core\JoinupRelationManagerInterface;
use Drupal\og\OgAccessInterface;
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
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   */
  public function pin(ContentEntityInterface $entity) {
    $collections = $this->getCollections($entity);

    if (count($collections) > 1) {
      // @todo Show a form to choose where to pin.
    }

    $collection = reset($collections);
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
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   */
  public function unpin(ContentEntityInterface $entity) {
    $collections = $this->getCollections($entity);

    if (count($collections) > 1) {
      // @todo Show a form to choose where to unpin.
    }

    $collection = reset($collections);
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
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function pinAccess(ContentEntityInterface $entity, AccountInterface $account) {
    if (!JoinupHelper::isSolution($entity) && !JoinupHelper::isCommunityContent($entity)) {
      return AccessResult::forbidden();
    }

    $collections = $this->getCollections($entity);
    if (empty($collections)) {
      return AccessResult::forbidden();
    }

    $cacheable_metadata = new CacheableMetadata();
    $cacheable_metadata->addCacheableDependency($entity);

    // Check if there is any collection where the entity can be pinned.
    foreach ($collections as $collection) {
      if (!$this->pinService->isEntityPinned($entity, $collection)) {
        // @todo merge all the cache metadata from each access check.
        $access = $this->ogAccess->userAccess($collection, 'pin group content', $account);
        if ($access->isAllowed()) {
          return $access->addCacheableDependency($cacheable_metadata);
        }
      }
    }

    return AccessResult::neutral()->addCacheableDependency($cacheable_metadata);
  }

  /**
   * Access check for the unpin route.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity being unpinned.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account to check access for.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function unpinAccess(ContentEntityInterface $entity, AccountInterface $account) {
    if (!JoinupHelper::isSolution($entity) && !JoinupHelper::isCommunityContent($entity)) {
      return AccessResult::forbidden();
    }

    $collections = $this->getCollections($entity);
    if (empty($collections)) {
      return AccessResult::forbidden();
    }

    $cacheable_metadata = new CacheableMetadata();
    $cacheable_metadata->addCacheableDependency($entity);

    // Check if there is any collection where the entity can be unpinned.
    foreach ($collections as $collection) {
      if ($this->pinService->isEntityPinned($entity, $collection)) {
        // @todo merge all the cache metadata from each access check.
        $access = $this->ogAccess->userAccess($collection, 'unpin group content', $account);
        if ($access->isAllowed()) {
          return $access->addCacheableDependency($cacheable_metadata);
        }
      }
    }

    return AccessResult::neutral()->addCacheableDependency($cacheable_metadata);
  }

  /**
   * Gets the collections an entity is related to.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity.
   *
   * @return \Drupal\rdf_entity\RdfInterface[]
   *   A list of collections the entity is related.
   */
  protected function getCollections(ContentEntityInterface $entity) {
    $collections = [];

    if (JoinupHelper::isSolution($entity)) {
      $collections = $entity->get('collection')->referencedEntities();
      uasort($collections, function ($a, $b) {
        return $a->id() <=> $b->id();
      });
    }
    elseif (JoinupHelper::isCommunityContent($entity)) {
      $collections = [$this->relationManager->getParent($entity)];
    }

    return $collections;
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
