<?php

namespace Drupal\joinup_subscription\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountProxy;

/**
 * Class SubscriptionSettingsForm.
 *
 * @package Drupal\joinup_subscription\Controller
 */
class SubscriptionSettingsForm extends ControllerBase {

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
   * Builds the subscription settings form.
   *
   * @return array
   *   The form array.
   */
  public function build() {
    $form = $this->entityFormBuilder()->getForm($this->currentUser);

    return $form;
  }

}
