<?php

namespace Drupal\collection\Cache;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines the collection cache context service.
 *
 * Cache context ID: 'collection'.
 */
class CollectionCacheContext extends ContainerAware implements CacheContextInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new CollectionCacheContext service.
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
    return t('Collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    /** @var \Drupal\rdf_entity\RdfInterface $collection */
    if (($collection = $this->requestStack->getCurrentRequest()->get('rdf_entity')) && $collection->bundle() == 'collection') {
      return 'collection.' . $collection->id();
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
