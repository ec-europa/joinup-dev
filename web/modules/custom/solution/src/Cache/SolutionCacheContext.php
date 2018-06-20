<?php

namespace Drupal\solution\Cache;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines the solution cache context service.
 *
 * Cache context ID: 'solution'.
 */
class SolutionCacheContext implements CacheContextInterface, ContainerAwareInterface {

  use ContainerAwareTrait;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new SolutionCacheContext service.
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
    return t('Solution');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    /** @var \Drupal\rdf_entity\RdfInterface $solution */
    if (($solution = $this->requestStack->getCurrentRequest()->get('rdf_entity')) && $solution->bundle() == 'solution') {
      return 'solution.' . $solution->id();
    }
    return 'solution.none';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

}
