<?php

declare(strict_types = 1);

namespace Drupal\easme_helper\Utility;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides helper functions for EASME project.
 */
class NotificationsHelper {

  use StringTranslationTrait;

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Constructs an instance of the NotificationHelper class.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   An instance of ConfigFactoryInterface.
   */
  public function __construct(ConfigFactory $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Provides the site name for notifications.
   *
   * @return string
   *   The site name.
   */
  public function getSiteName() {
    $easme_helper_config = $this->configFactory->get('easme_helper.settings');
    return $easme_helper_config->get('notifications.site_name');
  }

  /**
   * Provides the general notifications' signature.
   *
   * @return string
   *   The signature in HTML format.
   */
  public function getNotificationsSignature() {
    $easme_helper_config = $this->configFactory->get('easme_helper.settings');
    return $this->t('<p>Kind regards,</p><p>The @site_name Team</p>', ['@site_name' => $easme_helper_config->get('notifications.site_name')]);
  }

}
