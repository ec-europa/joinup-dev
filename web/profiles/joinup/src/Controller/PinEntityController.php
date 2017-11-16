<?php

namespace Drupal\joinup\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\joinup_community_content\CommunityContentHelper;
use Drupal\joinup_core\JoinupRelationManager;
use Drupal\node\NodeInterface;
use Drupal\og\OgAccessInterface;
use Drupal\rdf_entity\RdfInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller to pin/unpin entities inside collections.
 */
class PinEntityController extends ControllerBase {

  /**
   * The field that holds the collections where a solution is pinned in.
   *
   * @var string
   */
  const SOLUTION_PIN_FIELD = 'field_is_pinned_in';

  /**
   * The OG access service.
   *
   * @var \Drupal\og\OgAccessInterface
   */
  protected $ogAccess;

  /**
   * The Joinup relation manager.
   *
   * @var \Drupal\joinup_core\JoinupRelationManager
   */
  protected $relationManager;

  /**
   * Instantiates a new PinEntityController object.
   *
   * @param \Drupal\joinup_core\JoinupRelationManager $relationManager
   *   The Joinup relation manager.
   * @param \Drupal\og\OgAccessInterface $ogAccess
   *   The OG access service.
   */
  public function __construct(JoinupRelationManager $relationManager, OgAccessInterface $ogAccess) {
    $this->relationManager = $relationManager;
    $this->ogAccess = $ogAccess;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('joinup_core.relations_manager'),
      $container->get('og.access')
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
    $this->setEntitySticky($entity, $collection, TRUE);

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
    $this->setEntitySticky($entity, $collection, FALSE);

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
    if (!$this->isSolution($entity) && !$this->isCommunityContent($entity)) {
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
      if (!$this->isEntitySticky($entity, $collection)) {
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
    if (!$this->isSolution($entity) && !$this->isCommunityContent($entity)) {
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
      if ($this->isEntitySticky($entity, $collection)) {
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

    if ($this->isSolution($entity)) {
      $collections = $entity->get('collection')->referencedEntities();
    }
    elseif ($this->isCommunityContent($entity)) {
      $collections = [$this->relationManager->getParent($entity)];
    }

    return $collections;
  }

  /**
   * Returns whether the entity is an rdf solution.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to check.
   *
   * @return bool
   *   True if the entity is an rdf of bundle solution, false otherwise.
   */
  protected function isSolution(ContentEntityInterface $entity) {
    return $entity instanceof RdfInterface && $entity->bundle() === 'solution';
  }

  /**
   * Returns whether the entity is a community content node.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to check.
   *
   * @return bool
   *   True if the entity is a community content node, false otherwise.
   */
  protected function isCommunityContent(ContentEntityInterface $entity) {
    return $entity instanceof NodeInterface && in_array($entity->bundle(), CommunityContentHelper::getBundles());
  }

  /**
   * Checks if an entity is sticky inside a certain collection.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to check.
   * @param \Drupal\rdf_entity\RdfInterface $collection
   *   The rdf collection.
   *
   * @return bool
   *   True if the entity is sticky, false otherwise.
   */
  protected function isEntitySticky(ContentEntityInterface $entity, RdfInterface $collection) {
    if ($this->isSolution($entity)) {
      /** @var \Drupal\rdf_entity\RdfInterface $entity */
      foreach ($entity->get(self::SOLUTION_PIN_FIELD)->referencedEntities() as $rdf) {
        if ($rdf->id() === $collection->id()) {
          return TRUE;
        }
      }
    }
    elseif ($this->isCommunityContent($entity)) {
      // Nodes have only one possible parent, so the sticky boolean field
      // reflects the sticky status.
      /** @var \Drupal\node\NodeInterface $entity */
      return $entity->isSticky();
    }

    return FALSE;
  }

  /**
   * Sets the entity sticky status inside a certain collection.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity itself.
   * @param \Drupal\rdf_entity\RdfInterface $collection
   *   The rdf collection.
   * @param bool $sticky
   *   TRUE to set the entity as sticky, FALSE otherwise.
   */
  protected function setEntitySticky(ContentEntityInterface $entity, RdfInterface $collection, bool $sticky) {
    if ($this->isSolution($entity)) {
      $field = $entity->get(self::SOLUTION_PIN_FIELD);
      if ($sticky) {
        $field->appendItem($collection->id());
      }
      else {
        $field->filter(function ($item) use ($collection) {
          /** @var \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $item */
          return $item->target_id !== $collection->id();
        });
      }
    }
    elseif ($this->isCommunityContent($entity)) {
      // Nodes have only one possible parent, so the sticky boolean field
      // reflects the sticky status.
      /** @var \Drupal\node\NodeInterface $entity */
      $entity->setSticky($sticky);
    }

    $entity->save();
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
