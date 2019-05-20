<?php

declare(strict_types = 1);

namespace Drupal\joinup\Traits;

use Behat\MinkExtension\Context\RawMinkContext;

/**
 * Helper methods to inspect the cache status of the current page.
 *
 * These methods report if the current page is cached or cacheable by Drupal's
 * page cache and dynamic page cache. The standard page cache is used for
 * anonymous users without a session, while the dynamic page cache is used for
 * authenticated users and anonymous users with a session (e.g. anonymous users
 * with a shopping cart).
 *
 * This relies on the following setting to be enabled in `services.yml`:
 *   http.response.debug_cacheability_headers: true
 */
trait PageCacheTrait {

  /**
   * Returns the cache status of Drupal's page cache and dynamic page cache.
   *
   * @return array
   *   An associative array with the following keys:
   *   - X-Drupal-Cache: the status of Drupal's page cache. Can be one of:
   *     - 'HIT': The page is served from the page cache.
   *     - 'MISS': The page is not currently served from the page cache, but is
   *       cacheable and subsequent requests will be served from cache.
   *     - NULL: The page could not be served from the page cache. This may be
   *       because either the page_cache module is not enabled, the user has a
   *       session, or the page is not cacheable.
   *   - X-Drupal-Dynamic-Cache: the status of the dynamic page cache. One of:
   *     - 'HIT': The page is served from the dynamic page cache.
   *     - 'MISS': The page is not currently served from the page cache, but is
   *       cacheable and subsequent requests will be served from cache.
   *     - 'UNCACHEABLE': The page is uncacheable because it meets one or more
   *       of the conditions as defined in the `auto_placeholder_conditions`
   *       setting in `services.yml`.
   *     - NULL: The page could not be handled by the dynamic page cache. This
   *       can be because of various reasons: the dynamic_page_cache module is
   *       not enabled, the page has a 403 or 404 status, or one of the request
   *       or response policies are not met.
   */
  protected function getPageCacheStatus(): array {
    assert($this instanceof RawMinkContext, __METHOD__ . ' should only be included in Context classes that extend RawMinkContext.');

    /** @var \Behat\Mink\Session $session */
    $session = $this->getSession();

    return [
      'X-Drupal-Cache' => $session->getResponseHeader('X-Drupal-Cache'),
      'X-Drupal-Dynamic-Cache' => $session->getResponseHeader('X-Drupal-Dynamic-Cache'),
    ];
  }

  /**
   * Returns whether the current page is served from the cache.
   *
   * This can be either from the page cache or dynamic page cache.
   *
   * @return bool
   *   TRUE if the current page is served from the cache.
   */
  protected function isPageCached(): bool {
    return in_array('HIT', $this->getPageCacheStatus());
  }

  /**
   * Returns whether the current page is cacheable.
   *
   * @return bool
   *   TRUE if the current page is served from the cache, or eligible to be
   *   served from the cache on subsequent requests.
   */
  protected function isPageCacheable(): bool {
    return $this->isPageCached() || in_array('MISS', $this->getPageCacheStatus());
  }

}
