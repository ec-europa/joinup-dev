<?php

declare(strict_types = 1);

namespace Drupal\joinup\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\Cache\Context\UserCacheContextBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines a cache context service for authors of community content.
 *
 * This cache context should be used in any render elements that have different
 * content when shown to community content authors.
 *
 * Example use case: the content overview (a.k.a. Keep Up To Date page) has a
 * "My content" facet that is shown only to content authors.
 *
 * This is similar to UserCacheContext but is much less granular, since the
 * number of content authors is small relative to the total number of users.
 *
 * Cache context ID: 'community_content_author'
 */
class CommunityContentAuthorCacheContext extends UserCacheContextBase implements CacheContextInterface {

  /**
   * The string to return when no context is found.
   */
  const NO_CONTEXT = 'none';

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Static cache of queried cache contexts.
   *
   * @var string[]
   */
  protected $cachedContexts = [];

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Community content author');
  }

  /**
   * Constructs a new CommunityContentAuthorCacheContext object.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(AccountInterface $user, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($user);

    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getContext(): string {
    if ($this->user->isAnonymous()) {
      return self::NO_CONTEXT;
    }
    $uid = $this->user->id();

    // Since this might be called many times per request, only perform the query
    // once and cache it.
    if (!isset($this->cachedContexts[$uid])) {
      $query = $this->entityTypeManager->getStorage('node')->getQuery();
      $query
        ->condition('uid', $uid)
        ->range(0, 1);
      $this->cachedContexts[$uid] = empty($query->execute()) ? self::NO_CONTEXT : (string) $uid;
    }

    return $this->cachedContexts[$uid];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

}
