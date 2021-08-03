<?php

declare(strict_types = 1);

namespace Drupal\easme_helper\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\cas\Event\CasPreRegisterEvent;
use Drupal\cas\Service\CasHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * CasEventSubscriber class.
 */
class CasEventSubscriber implements EventSubscriberInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new CasEventSubscriber.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   *
   * @return array
   *   The event names to listen for, and the methods that should be executed.
   */
  public static function getSubscribedEvents() {
    return [
      CasHelper::EVENT_PRE_REGISTER => ['userPreRegister', 100],
    ];
  }

  /**
   * React to a user logging in with cas when user does not yet exist.
   *
   * @param \Drupal\cas\Event\CasPreRegisterEvent $event
   *   Cas pre register event.
   */
  public function userPreRegister(CasPreRegisterEvent $event) {
    // Prevent the creation of the user account.
    $event->setAllowAutomaticRegistration(FALSE);

    // Redirect the user to the Community site login process.
    $url = $this->configFactory->get('easme_helper.settings')->get('urls.community_site') . '/ecas';
    $response = new TrustedRedirectResponse($url);
    $response->send();
  }

}
