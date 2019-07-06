<?php

declare(strict_types = 1);

namespace Drupal\joinup_eulogin;

use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Default implementation of joinup_eulogin.computed_attributes service.
 */
class JoinupEuLoginComputedAttributes implements JoinupEuLoginComputedAttributesInterface {

  /**
   * The key-value store.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $storage;

  /**
   * The log channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $log;

  /**
   * Builds a new service instance.
   *
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value_factory
   *   The key-value factory service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory service.
   */
  public function __construct(KeyValueFactoryInterface $key_value_factory, LoggerChannelFactoryInterface $logger_factory) {
    $this->storage = $key_value_factory->get('joinup');
    $this->log = $logger_factory->get('joinup');
  }

  /**
   * {@inheritdoc}
   */
  public function getReplacementValue(string $attribute, string $original_token, array $cas_attributes, BubbleableMetadata $bubbleable_metadata): ?string {
    switch ($attribute) {
      case 'organisation':
        return $this->getOrganisationValue($cas_attributes);
    }
    return $original_token;
  }

  /**
   * Computes the organisation name.
   *
   * @param array $cas_attributes
   *   The CAS attributes.
   *
   * @return string|null
   *   The organisation name or NULL if it cannot be computed.
   */
  protected function getOrganisationValue(array $cas_attributes): ?string {
    $domain = $cas_attributes['domain'] ?? NULL;
    if (!$domain) {
      return NULL;
    }

    // In the published ECAS schema the domain is defined as a string. The CAS
    // module might deliver us an array of data but this should only contain a
    // single value. Log a warning if more values are encountered so we are
    // alerted that the schema has changed.
    // @see https://ecas.ec.europa.eu/cas/schemas
    if (is_array($domain)) {
      if (count($domain) > 1) {
        $this->log->warning('Received multiple domains from ECAS for a single user. All domains except the first have been discarded.');
      }
      $domain = reset($domain);
    }

    $data = $this->storage->get('eulogin.schema');
    if (empty($data['organisations'])) {
      // Fail softly. We don't want to ruin the user experience on login, we
      // only log the problem.
      $this->log->error('The list of organisations is not stored in joinup:eulogin.schema.');
      return NULL;
    }

    return $data['organisations'][$domain] ?? NULL;
  }

}
