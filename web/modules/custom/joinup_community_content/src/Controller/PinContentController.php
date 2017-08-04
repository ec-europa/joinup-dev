<?php

namespace Drupal\joinup_community_content\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\joinup_community_content\CommunityContentHelper;
use Drupal\joinup_core\JoinupRelationManager;
use Drupal\node\NodeInterface;
use Drupal\og\MembershipManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller to pin/unpin community content.
 */
class PinContentController extends ControllerBase {

  /**
   * The OG membership manager service.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $membershipManager;

  /**
   * The Joinup relation manager.
   *
   * @var \Drupal\joinup_core\JoinupRelationManager
   */
  protected $relationManager;

  /**
   * Instantiates a new PinContentController object.
   *
   * @param \Drupal\joinup_core\JoinupRelationManager $relationManager
   *   The Joinup relation manager.
   * @param \Drupal\og\MembershipManagerInterface $membershipManager
   *   The OG membership manager service.
   */
  public function __construct(JoinupRelationManager $relationManager, MembershipManagerInterface $membershipManager) {
    $this->relationManager = $relationManager;
    $this->membershipManager = $membershipManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('joinup_core.relations_manager'),
      $container->get('og.membership_manager')
    );
  }

  /**
   * Pins a node inside the collection it belongs.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity being pinned.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   */
  public function pin(NodeInterface $node) {
    $node->setSticky(TRUE)->save();

    drupal_set_message($this->t('@bundle %title has been pinned in the collection %collection.', [
      '@bundle' => $node->get('type')->entity->label(),
      '%title' => $node->label(),
      '%collection' => $this->relationManager->getParent($node)->label(),
    ]));

    return $this->getRedirect($node);
  }

  /**
   * Unpins a node inside the collection it belongs.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity being unpinned.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   */
  public function unpin(NodeInterface $node) {
    $node->setSticky(FALSE)->save();

    drupal_set_message($this->t('@bundle %title has been unpinned in the collection %collection.', [
      '@bundle' => $node->get('type')->entity->label(),
      '%title' => $node->label(),
      '%collection' => $this->relationManager->getParent($node)->label(),
    ]));

    return $this->getRedirect($node);
  }

  /**
   * Access check for the pin route.
   *
   * A node can be pinned only if it's not pinned, if its bundle is a community
   * content one and if the user is a facilitator in the parent collection.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity being pinned.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account to check access for.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function pinAccess(NodeInterface $node, AccountInterface $account) {
    return AccessResult::allowedIf(
      !$node->isSticky() &&
      in_array($node->bundle(), CommunityContentHelper::getBundles()) &&
      $this->isFacilitatorInParentCollection($node, $account)
    );
  }

  /**
   * Access check for the unpin route.
   *
   * A node can be unpinned only if it's pinned, if its bundle is a community
   * content one and if the user is a facilitator in the parent collection.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity being unpinned.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account to check access for.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function unpinAccess(NodeInterface $node, AccountInterface $account) {
    return AccessResult::allowedIf(
      $node->isSticky() &&
      in_array($node->bundle(), CommunityContentHelper::getBundles()) &&
      $this->isFacilitatorInParentCollection($node, $account)
    );
  }

  /**
   * Checks if a user has facilitator role in the parent collection of a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to check the OG role for.
   *
   * @return bool
   *   True if the user has the facilitator role in the parent of the node.
   */
  protected function isFacilitatorInParentCollection(NodeInterface $node, AccountInterface $account) {
    $collection = $this->relationManager->getParent($node);

    if (!$collection || $collection->bundle() !== 'collection') {
      return FALSE;
    }

    $membership = $this->membershipManager->getMembership($collection, $account);
    return !empty($membership) && $membership->hasRole('rdf_entity-collection-facilitator');
  }

  /**
   * Returns a response to redirect the user to the collection of the node.
   *
   * @param \Drupal\Core\Entity\EntityInterface $node
   *   The node being handled.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response to the node collection.
   */
  protected function getRedirect(EntityInterface $node) {
    $redirect = $this->relationManager->getParent($node)->toUrl();

    return $this->redirect($redirect->getRouteName(), $redirect->getRouteParameters());
  }

}
