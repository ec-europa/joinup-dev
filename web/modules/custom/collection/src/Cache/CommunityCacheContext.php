<?php

declare(strict_types = 1);

namespace Drupal\collection\Cache;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines the collection cache context service.
 *
 * Cache context ID: 'collection'.
 */
class CommunityCacheContext implements CacheContextInterface, ContainerAwareInterface {

  use ContainerAwareTrait;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new CommunityCacheContext service.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(RequestStack $request_stack) {
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Community');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    /** @var \Drupal\rdf_entity\RdfInterface $community */
    if (($community = $this->requestStack->getCurrentRequest()->get('rdf_entity')) && $community->bundle() == 'collection') {
      return 'collection.' . $community->id();
    }
    return 'collection.none';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    $meta_data = new CacheableMetadata();
    $meta_data->setCacheMaxAge(0);
    return $meta_data;
  }

}
