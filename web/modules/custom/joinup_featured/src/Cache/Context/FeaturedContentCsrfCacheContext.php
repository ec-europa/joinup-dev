<?php

declare(strict_types = 1);

namespace Drupal\joinup_featured\Cache\Context;

use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;

/**
 * Cache context for CSRF protected links for (un)pinning content.
 *
 * Cache context ID: 'joinup_featured_csrf'.
 *
 * The contextual links that allow content to be features inside a group are
 * protected against CSRF using a session based token. This cache context is in
 * fact similar to the 'session' cache context, but we are not using that one
 * since we still want the pages that show these links to be marked as cacheable
 * by the dynamic page cache.
 * This comes with the same side effects as the session cache context: it also
 * introduces a high granularity in the cache. We are still using it since the
 * contextual links placeholders take up very little cache space, and we don't
 * want to lose the ability to detect regressions in cacheability of important
 * pages such as the collections / solutions overviews and homepages.
 */
class FeaturedContentCsrfCacheContext implements CacheContextInterface {

  /**
   * The CSRF token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $csrfTokenGenerator;

  /**
   * Constructs a new FeaturedContentCsrfCacheContext.
   *
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrf_token_generator
   *   The CSRF token generator.
   */
  public function __construct(CsrfTokenGenerator $csrf_token_generator) {
    $this->csrfTokenGenerator = $csrf_token_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Featured content CSRF token');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return $this->csrfTokenGenerator->get(__CLASS__);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

}
