<?php

namespace Drupal\asset_release\Cache;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines the asset_release cache context service.
 *
 * Cache context ID: 'asset_release'.
 */
class AssetReleaseCacheContext extends ContainerAware implements CacheContextInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new AssetReleaseCacheContext service.
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
    return t('AssetRelease');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    /** @var \Drupal\rdf_entity\RdfInterface $asset_release */
    if (($asset_release = $this->requestStack->getCurrentRequest()->get('rdf_entity')) && $asset_release->bundle() == 'asset_release') {
      return 'asset_release.' . $asset_release->id();
    }
    return 'asset_release.none';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

}
