<?php

namespace Drupal\easme_helper\Controller;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\RedirectResponse;

/**
 * Controller routines for general routes.
 */
class GeneralController extends ControllerBase {

  /**
   * The easme_settings configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig;
   */
  protected $easme_config;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   An instance of ConfigFactoryInterface.
   */
  public function __construct(ConfigFactory $config_factory) {
    $this->configFactory = $config_factory;
    $this->easme_config = $this->configFactory->get('easme_helper.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * Redirects users coming from the Community to the defined page.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Returns a redirect to the defined page.
   */
  public function redirectFromCommunitySite() {
    $message = t("Thank you. Your profile will be updated shortly.");
    \Drupal::messenger()->addMessage($message);
    return $this->redirect($this->easme_config->get('urls.redirect_from_community_site'));
  }

  /**
   * Redirects users to pre-defined contact.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Returns a redirect to the Community site contact page.
   */
  public function contactPage() {
    return new TrustedRedirectResponse($this->easme_config->get('urls.community_site') . '/contact?back_to=challenge');
  }

}
