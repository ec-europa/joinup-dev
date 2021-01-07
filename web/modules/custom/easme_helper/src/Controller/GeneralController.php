<?php

namespace Drupal\easme_helper\Controller;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\RedirectResponse;

/**
 * Controller routines for general routes.
 */
class GeneralController extends ControllerBase {

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   An instance of ConfigFactoryInterface.
   */
  public function __construct(ConfigFactory $config_factory) {
    $this->configFactory = $config_factory;
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
    $config = $this->configFactory->get('easme_helper.settings');
    $message = t("Thank you. Your profile will be updated shortly.");
    \Drupal::messenger()->addMessage($message);
    return $this->redirect($config->get('urls.redirect_from_community_site'));
  }

}
