<?php

declare(strict_types = 1);

namespace Drupal\joinup_cas_mock_server\Config;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Overrides some configs for testing purposes.
 */
class JoinupCasMockServerConfigOverrider implements ConfigFactoryOverrideInterface {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new overrider instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names): array {
    return [
      // Configure the CAS authentication form so it uses the same interface
      // text as on EU Login.
      'cas_mock_server.settings' => [
        'login_form' => [
          'title' => 'Sign in to continue',
          'email' => 'E-mail address',
        ],
      ],
      // The Joinup EU Login module customizes the validation path of the CAS
      // ticket validation service. Restore it to the value that corresponds to
      // the CAS 3.0 specification which is implemented by the CAS mock server.
      'joinup_eulogin.settings' => [
        'ticket_validation' => [
          'path' => 'p3/serviceValidate',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'joinup_cas_mock_server';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    $metadata = new CacheableMetadata();
    if (in_array($name, ['cas_mock_server.settings', 'joinup_eulogin.settings'])) {
      $metadata->addCacheableDependency(\Drupal::configFactory()->get($name));
    }
    return $metadata;
  }

}
