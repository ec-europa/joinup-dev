<?php

declare(strict_types = 1);

namespace Drupal\joinup_eulogin;

use Drupal\Component\Datetime\Time;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\State\StateInterface;
use GuzzleHttp\ClientInterface;

/**
 * Default implementation of 'joinup_eulogin.schema_data_updater' service.
 */
class JoinupEuLoginSchemaDataUpdater implements JoinupEuLoginSchemaDataUpdaterInterface {

  /**
   * The HTTP client service.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The config factory interface service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The key-value store.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $storage;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\Time
   */
  protected $time;

  /**
   * Builds a new service instance.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory interface service.
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value_factory
   *   The key-value factory service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Component\Datetime\Time $time
   *   The time service.
   */
  public function __construct(ClientInterface $http_client, ConfigFactoryInterface $config_factory, KeyValueFactoryInterface $key_value_factory, StateInterface $state, Time $time) {
    $this->httpClient = $http_client;
    $this->configFactory = $config_factory;
    $this->storage = $key_value_factory->get('joinup');
    $this->state = $state;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public function update(): bool {
    $config = $this->configFactory->get('joinup_eulogin.settings');
    $url = $config->get('schema.url');

    try {
      $response = $this->httpClient->request('GET', $url);
    }
    catch (\Exception $exception) {
      // Exit silently. We don't want the update the stored data if a HTTP or
      // a network exception occurs.
      return FALSE;
    }

    $data = $this->storage->get('eulogin.schema', []);
    $xml = new \SimpleXMLElement($response->getBody()->getContents());
    $version = $xml->attributes()['version']->__toString();

    if (!empty($data['version']) && ($version === $data['version'])) {
      // The stored schema is already up-to-date.
      return FALSE;
    }

    $domain_elements = $xml->xpath('//xsd:simpleType[@name="domainType"]/xsd:restriction[@base="xsd:string"]/xsd:enumeration');
    $organisations = [];
    foreach ($domain_elements as $element) {
      $key = $element->attributes()['value']->__toString();
      $name_element = $element->xpath('xsd:annotation/xsd:documentation');
      // Normalize the value. It might contain consecutive spaces.
      $value = preg_replace('/\s{2,}/', ' ', $name_element[0]->__toString());
      $organisations[$key] = $value;
    }

    $this->storage->set('eulogin.schema', [
      'version' => $version,
      'organisations' => $organisations,
    ]);
    $this->state->set('joinup_eulogin.schema_data_updater.last_updated', $this->time->getRequestTime());

    return TRUE;
  }

}
