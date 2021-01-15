<?php

declare(strict_types = 1);

namespace Drupal\dashboard\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Page controllers for the Dashboard module.
 */
class DashboardController extends ControllerBase {

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
   * Renders the main dashboard page.
   */
  public function page() {
    $user_id = $this->currentUser->id();
    $my_subscriptions_url = Url::fromRoute('joinup_subscription.my_subscriptions', [
      'user' => $user_id,
    ]);

    $links['my_subscriptions'] = [
      'title' => $this->t('My subscriptions'),
      'url' => $my_subscriptions_url,
      'attributes' => ['class' => ['button', 'button--small']],
    ];

    $licences_url = Url::fromRoute('joinup_licence.overview');
    $links['licences'] = [
      'title' => $this->t('Licences overview'),
      'url' => $licences_url,
      'attributes' => ['class' => ['button', 'button--small']],
    ];

    $links = array_filter($links, function ($link) {
      return $link['url']->access();
    });

    $links = [
      '#theme' => 'links',
      '#links' => $links,
      '#attributes' => ['class' => ['links--buttons']],
    ];

    return $links;
  }

}
