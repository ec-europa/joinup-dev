<?php

declare(strict_types = 1);

namespace Drupal\joinup\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\Cache\Context\UserCacheContextBase;

/**
 * A cache context that allows showing variants of elements to the root user.
 *
 * Cache context ID: 'user.is_root'.
 */
class RootUserCacheContext extends UserCacheContextBase implements CacheContextInterface {

  /**
   * A cache context key indicating that the current user is the root user.
   */
  const IS_ROOT = 'true';

  /**
   * A cache context key indicating that the current user is not the root user.
   */
  const IS_NOOT = 'false';

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Root user');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return $this->getUserId() === 1 ? self::IS_ROOT : self::IS_NOOT;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata(): CacheableMetadata {
    return new CacheableMetadata();
  }

  /**
   * Returns the user ID.
   *
   * @return int
   *   The user ID, or 0 for anonymous users.
   */
  protected function getUserId(): int {
    return (int) $this->user->id();
  }

}
