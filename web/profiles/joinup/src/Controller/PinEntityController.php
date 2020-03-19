<?php

declare(strict_types=1);

namespace Drupal\joinup\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\joinup\PinServiceInterface;
use Drupal\joinup_community_content\CommunityContentHelper;
use Drupal\joinup_group\JoinupGroupRelationInfoInterface;
use Drupal\joinup_group\JoinupGroupHelper;
use Drupal\og\OgAccessInterface;
use Drupal\rdf_entity\RdfInterface;
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
   * The pin service.
   *
   * @var \Drupal\joinup\PinServiceInterface
   */
  protected $pinService;

  /**
   * The Joinup group relation info service.
   *
   * @var \Drupal\joinup_group\JoinupGroupRelationInfoInterface
   */
  protected $relationInfo;

  /**
   * Instantiates a new PinEntityController object.
   *
   * @param \Drupal\joinup_group\JoinupGroupRelationInfoInterface $relationInfo
   *   The Joinup group relation info service.
   * @param \Drupal\og\OgAccessInterface $ogAccess
   *   The OG access service.
   * @param \Drupal\joinup\PinServiceInterface $pinService
   *   The pin service.
   */
  public function __construct(JoinupGroupRelationInfoInterface $relationInfo, OgAccessInterface $ogAccess, PinServiceInterface $pinService) {
    $this->relationInfo = $relationInfo;
    $this->ogAccess = $ogAccess;
    $this->pinService = $pinService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('joinup_group.relation_info'),
      $container->get('og.access'),
      $container->get('joinup.pin_service')
    );
  }

  /**
   * Pins a group content entity inside a group.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity being pinned.
   * @param \Drupal\rdf_entity\RdfInterface $group
   *   The group where to pin the content.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   */
  public function pin(ContentEntityInterface $entity, RdfInterface $group) {
    $this->pinService->setEntityPinned($entity, $group, TRUE);

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
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity being unpinned.
   * @param \Drupal\rdf_entity\RdfInterface $group
   *   The group where to unpin the content.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   */
  public function unpin(ContentEntityInterface $entity, RdfInterface $group) {
    $this->pinService->setEntityPinned($entity, $group, FALSE);

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
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function pinAccess(ContentEntityInterface $entity, AccountInterface $account, RdfInterface $group) {
    if (!$this->validEntityParameters($entity, $group)) {
      return AccessResult::forbidden();
    }

    if (
      !array_key_exists($group->id(), $this->getGroups($entity)) ||
      $this->pinService->isEntityPinned($entity, $group)
    ) {
      return AccessResult::forbidden()->addCacheableDependency($group)->addCacheableDependency($entity);
    }

    return $this->ogAccess->userAccess($group, 'pin group content', $account);
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
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function unpinAccess(ContentEntityInterface $entity, AccountInterface $account, RdfInterface $group) {
    if (!$this->validEntityParameters($entity, $group)) {
      return AccessResult::forbidden();
    }

    if (
      !array_key_exists($group->id(), $this->getGroups($entity)) ||
      !$this->pinService->isEntityPinned($entity, $group)
    ) {
      return AccessResult::forbidden()->addCacheableDependency($group)->addCacheableDependency($entity);
    }

    return $this->ogAccess->userAccess($group, 'unpin group content', $account);
  }

  /**
   * Gets the groups an entity is related to.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity.
   *
   * @return \Drupal\rdf_entity\RdfInterface[]
   *   A list of groups the entity is related, keyed by group id.
   */
  protected function getGroups(ContentEntityInterface $entity) {
    $groups = [];

    if (JoinupGroupHelper::isSolution($entity)) {
      $groups = $entity->get('collection')->referencedEntities();
    }
    elseif (CommunityContentHelper::isCommunityContent($entity)) {
      $groups = [$this->relationInfo->getParent($entity)];
    }

    $list = [];
    foreach ($groups as $group) {
      $list[$group->id()] = $group;
    }

    return $list;
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
    if (JoinupGroupHelper::isSolution($entity)) {
      return JoinupGroupHelper::isCollection($group);
    }
    elseif (CommunityContentHelper::isCommunityContent($entity)) {
      return JoinupGroupHelper::isCollection($group) || JoinupGroupHelper::isSolution($group);
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
