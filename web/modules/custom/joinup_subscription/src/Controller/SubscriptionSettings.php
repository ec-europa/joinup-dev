<?php

namespace Drupal\joinup_subscription\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountProxy;

/**
 * Controller that shows the subscription settings form.
 *
 * @todo Rename to MySubscriptionsController
 *
 * @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-5447
 */
class SubscriptionSettings extends ControllerBase {

  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Constructs a new SubscriptionSettings form.
   *
   * @param \Drupal\Core\Session\AccountProxy $current_user
   *   The current user.
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
   * Displays the subscription dashboard for the currently logged in user.
   *
   * This controller assumes that it is only invoked for authenticated users.
   * This is enforced for the 'joinup_subscription.my_subscriptions' route with
   * the '_user_is_logged_in' requirement.
   *
   * @return array
   *   The subscription dashboard form array.
   */
  public function build() {
    return $this->formBuilder()->getForm('Drupal\joinup_subscription\Form\SubscriptionDashboardForm', $this->currentUser());
  }

  /**
   * Access control for the subscription settings user page.
   *
   * The user is checked for both global permissions and permissions to edit
   * their own subscriptions.
   *
   * @param \Drupal\Core\Entity\EntityInterface $user
   *   The user object from the route.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   An access result object carrying the result of the check.
   */
  public function access(EntityInterface $user) {
    // Users that can administer all users have access.
    if ($this->currentUser->hasPermission('administer users')) {
      return AccessResult::allowed();
    }
    // The logged in user can manage their own subscriptions.
    elseif (!$this->currentUser->isAnonymous() && $this->currentUser->id() == $user->id()) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

}
