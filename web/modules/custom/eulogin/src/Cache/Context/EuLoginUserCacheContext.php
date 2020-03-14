<?php

declare(strict_types = 1);

namespace Drupal\joinup_eulogin\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\Cache\Context\UserCacheContextBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Varies the cache by whether the current user has an EU Login linked account.
 *
 * The cache context returns:
 * - 'y': the current user has an EU Login linked account.
 * - 'n': the current user is not linked to an EU Login account.
 * - '0': the current user is anonymous.
 *
 * Cache context ID: 'user.is_eulogin'.
 */
class EuLoginUserCacheContext extends UserCacheContextBase implements CacheContextInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new cache context instance.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(AccountInterface $user, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($user);
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Is EU Login user');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    if ($this->user->isAnonymous()) {
      return '0';
    }

    /** @var \Drupal\user\UserInterface $account */
    $account = $this->entityTypeManager->getStorage('user')->load($this->user->id());

    return !$account->get('eulogin_authname')->isEmpty() ? 'y' : 'n';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata(): CacheableMetadata {
    $cache_metadata = new CacheableMetadata();
    $cache_metadata->addCacheTags(["user:{$this->user->id()}"]);
    return $cache_metadata;
  }

}
