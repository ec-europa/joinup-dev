<?php

declare(strict_types = 1);

namespace Drupal\joinup\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\collection\Entity\CollectionInterface;
use Drupal\joinup_community_content\Entity\CommunityContentInterface;
use Drupal\joinup_group\Entity\GroupInterface;
use Drupal\joinup_group\Entity\PinnableGroupContentInterface;
use Drupal\og\OgAccessInterface;
use Drupal\rdf_entity\RdfInterface;
use Drupal\solution\Entity\SolutionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller to pin/unpin entities inside groups.
 */
class PinEntityController extends ControllerBase {

  /**
   * The OG access service.
   *
   * @var \Drupal\og\OgAccessInterface
   */
  protected $ogAccess;

  /**
   * Instantiates a new PinEntityController object.
   *
   * @param \Drupal\og\OgAccessInterface $ogAccess
   *   The OG access service.
   */
  public function __construct(OgAccessInterface $ogAccess) {
    $this->ogAccess = $ogAccess;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('og.access')
    );
  }

  /**
   * Pins a group content entity inside a group.
   *
   * @param \Drupal\joinup_group\Entity\PinnableGroupContentInterface $entity
   *   The content entity being pinned.
   * @param \Drupal\joinup_group\Entity\GroupInterface $group
   *   The group where to pin the content.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   */
  public function pin(PinnableGroupContentInterface $entity, GroupInterface $group) {
    $entity->pin($group);

    $this->messenger()->addMessage($this->t('@bundle %title has been pinned in the @group_bundle %group.', [
      '@bundle' => $entity->get($entity->getEntityType()->getKey('bundle'))->entity->label(),
      '%title' => $entity->label(),
      '@group_bundle' => $group->bundle(),
      '%group' => $group->label(),
    ]));

    return $this->getRedirect($group);
  }

  /**
   * Unpins a group content entity inside a group.
   *
   * @param \Drupal\joinup_group\Entity\PinnableGroupContentInterface $entity
   *   The content entity being unpinned.
   * @param \Drupal\joinup_group\Entity\GroupInterface $group
   *   The group where to unpin the content.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   */
  public function unpin(PinnableGroupContentInterface $entity, GroupInterface $group) {
    $entity->unpin($group);

    $this->messenger()->addMessage($this->t('@bundle %title has been unpinned in the @group_bundle %group.', [
      '@bundle' => $entity->get($entity->getEntityType()->getKey('bundle'))->entity->label(),
      '%title' => $entity->label(),
      '@group_bundle' => $group->bundle(),
      '%group' => $group->label(),
    ]));

    return $this->getRedirect($group);
  }

  /**
   * Access check for the pin route.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity being pinned.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account to check access for.
   * @param \Drupal\rdf_entity\RdfInterface $group
   *   The group where to pin the content.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function pinAccess(ContentEntityInterface $entity, AccountInterface $account, RdfInterface $group) {
    if (!$this->validEntityParameters($entity, $group)) {
      return AccessResult::forbidden();
    }

    /** @var \Drupal\joinup_group\Entity\PinnableGroupContentInterface $entity */
    /** @var \Drupal\joinup_group\Entity\GroupInterface $group */
    if (
      !in_array($group->id(), $entity->getPinnableGroupIds()) ||
      $entity->isPinned($group)
    ) {
      return AccessResult::forbidden()->addCacheableDependency($group)->addCacheableDependency($entity);
    }

    return $this->ogAccess->userAccess($group, 'pin group content', $account)->addCacheableDependency($entity);
  }

  /**
   * Access check for the unpin route.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity being unpinned.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account to check access for.
   * @param \Drupal\rdf_entity\RdfInterface $group
   *   The group where to pin the content.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function unpinAccess(ContentEntityInterface $entity, AccountInterface $account, RdfInterface $group) {
    if (!$this->validEntityParameters($entity, $group)) {
      return AccessResult::forbidden();
    }

    /** @var \Drupal\joinup_group\Entity\PinnableGroupContentInterface $entity */
    /** @var \Drupal\joinup_group\Entity\GroupInterface $group */
    if (
      !in_array($group->id(), $entity->getPinnableGroupIds()) ||
      !$entity->isPinned($group)
    ) {
      return AccessResult::forbidden()->addCacheableDependency($group)->addCacheableDependency($entity);
    }

    return $this->ogAccess->userAccess($group, 'unpin group content', $account)->addCacheableDependency($entity);
  }

  /**
   * Validates the types of the entities passed to callbacks.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity that is going to be pinned.
   * @param \Drupal\rdf_entity\RdfInterface $group
   *   The group where to pin the entity.
   *
   * @return bool
   *   True if the entities are of the expected types, false otherwise.
   */
  protected function validEntityParameters(ContentEntityInterface $entity, RdfInterface $group) {
    // Do not make this generic because we don't want any solution appearing
    // in the solution overview - as related solutions - to retrieve the
    // pin/unpin contextual link.
    if ($entity instanceof SolutionInterface) {
      return $group instanceof CollectionInterface;
    }
    elseif ($entity instanceof CommunityContentInterface) {
      return $group instanceof GroupInterface;
    }
    return FALSE;
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
