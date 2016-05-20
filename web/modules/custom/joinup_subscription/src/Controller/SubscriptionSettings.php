<?php

namespace Drupal\joinup_subscription\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountProxy;

/**
 * Class SubscriptionSettings.
 *
 * @package Drupal\joinup_subscription\Controller
 */
class SubscriptionSettings extends ControllerBase {

  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public function __construct(AccountProxy $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('current_user'));
  }

  /**
   * Builds the subscription_settings form for the user element.
   *
   * @return array
   *   The form array.
   */
  public function build($user) {
    $form = $this->entityFormBuilder()->getForm($user, 'subscription_settings');
    return $form;
  }

  /**
   * Access control for the subscription settings user page.
   *
   * The user is checked for both global permissions and permissions to edit
   * his own subscriptions.
   *
   * @param \Drupal\Core\Entity\EntityInterface $user
   *    The user object from the route.
   *
   * @return \Drupal\Core\Access\AccessResult
   *    An access result object carrying the result of the check.
   */
  public function access(EntityInterface $user) {
    if ($this->currentUser->hasPermission('manage all subscriptions')) {
      return AccessResult::allowed();
    }
    elseif (!$this->currentUser->isAnonymous() && $this->currentUser->id() == $user->id() && $this->currentUser->hasPermission('manage own subscriptions')) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

}
