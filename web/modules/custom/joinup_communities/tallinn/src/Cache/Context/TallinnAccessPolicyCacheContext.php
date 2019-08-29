<?php

namespace Drupal\tallinn\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Defines a cache context that varies by the Tallinn Access Policy.
 *
 * Cache context ID: 'tallinn_access_policy'.
 */
class TallinnAccessPolicyCacheContext implements CacheContextInterface, ContainerAwareInterface {

  use ContainerAwareTrait;

  /**
   * The state key value store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a new TallinnAccessPolicyCacheContext.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key value store.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Tallinn access policy');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return $this->state->get('tallinn.access_policy', 'restricted');
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

}
