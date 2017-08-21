<?php

namespace Drupal\joinup_community_content\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\joinup_community_content\CommunityContentHelper;
use Drupal\joinup_core\JoinupRelationManager;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller to feature/unfeature community content.
 */
class FeatureContentController extends ControllerBase {

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
   * Features a community content.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity being featured.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   */
  public function feature(NodeInterface $node) {
    $node->set('featured', TRUE)->save();

    drupal_set_message($this->t('@bundle %title has been set as featured content.', [
      '@bundle' => $node->get('type')->entity->label(),
      '%title' => $node->label(),
    ]));

    return $this->getRedirect($node);
  }

  /**
   * Unfeatures a community content.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity being removed from featured content.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   */
  public function unfeature(NodeInterface $node) {
    $node->set('featured', FALSE)->save();

    drupal_set_message($this->t('@bundle %title has been removed from the feature contents.', [
      '@bundle' => $node->get('type')->entity->label(),
      '%title' => $node->label(),
    ]));

    return $this->getRedirect($node);
  }

  /**
   * Access check for the feature route.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity being featured.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account to check access for.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function featureAccess(NodeInterface $node, AccountInterface $account) {
    return AccessResult::allowedIf(in_array($node->bundle(), CommunityContentHelper::getBundles()) && !$node->get('featured')->value);
  }

  /**
   * Access check for the unfeature route.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity being removed from featured content.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account to check access for.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function unfeatureAccess(NodeInterface $node, AccountInterface $account) {
    return AccessResult::allowedIf(in_array($node->bundle(), CommunityContentHelper::getBundles()) && $node->get('featured')->value);
  }

  /**
   * Returns a response to redirect the user to the collection of the node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node being handled.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response to the node collection.
   */
  protected function getRedirect(NodeInterface $node) {
    $redirect = $this->relationManager->getParent($node)->toUrl();

    return $this->redirect($redirect->getRouteName(), $redirect->getRouteParameters());
  }

}
