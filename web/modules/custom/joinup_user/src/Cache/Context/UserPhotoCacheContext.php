<?php

declare(strict_types = 1);

namespace Drupal\joinup_user\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\Cache\Context\UserCacheContextBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\TypedData\Exception\MissingDataException;
use Drupal\file\FileInterface;
use Drupal\file\FileStorageInterface;
use Drupal\user\UserInterface;
use Drupal\user\UserStorageInterface;

/**
 * A cache context to allow to vary elements by the user photo that is shown.
 *
 * Since only a minority of users have a profile photo this cache context will
 * provide better results than the very granular `user` cache context.
 *
 * Cache context ID: 'user.photo'.
 */
class UserPhotoCacheContext extends UserCacheContextBase implements CacheContextInterface {

  /**
   * A cache context key indicating that the user doesn't have a photo.
   */
  const NO_PHOTO = 'no-user-photo';

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a UserPhotoCacheContext object.
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
    return t('User photo');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return $this->getUserPhotoId() ?? self::NO_PHOTO;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata(): CacheableMetadata {
    $cacheable_metadata = new CacheableMetadata();

    if ($photo = $this->getUserPhoto()) {
      $cacheable_metadata->addCacheableDependency($photo);
    }

    return $cacheable_metadata;
  }

  /**
   * Returns the file ID of the user's photo.
   *
   * @return string|null
   *   The file ID, or NULL if the user doesn't have a photo.
   */
  protected function getUserPhotoId(): ?string {
    // If the user is anonymous the full user entity is not available.
    $user = $this->getUser();
    if ($user instanceof UserInterface) {
      try {
        $photo_field = $user->get('field_user_photo')->first();
        if ($photo_field) {
          return $photo_field->getValue()['target_id'] ?? NULL;
        }
      }
      catch (MissingDataException $e) {
        return NULL;
      }
    }
    return NULL;
  }

  /**
   * Returns the user photo.
   *
   * @return \Drupal\file\FileInterface|null
   *   The user photo, or NULL if the user doesn't have a photo.
   */
  protected function getUserPhoto(): ?FileInterface {
    if ($photo_id = $this->getUserPhotoId()) {
      return $this->getFileStorage()->load($photo_id);
    }
    return NULL;
  }

  /**
   * Returns the account of the current user.
   *
   * @return \Drupal\Core\Session\AccountInterface|null
   *   The user account, or NULL if the user is not logged in.
   */
  protected function getUser(): ?AccountInterface {
    if ($this->user instanceof AccountProxyInterface) {
      $this->user = $this->getUserStorage()->load($this->user->id());
    }

    return $this->user;
  }

  /**
   * Returns the file storage handler.
   *
   * @return \Drupal\file\FileStorageInterface
   *   The file storage handler.
   */
  protected function getFileStorage(): FileStorageInterface {
    return $this->entityTypeManager->getStorage('file');
  }

  /**
   * Returns the user storage handler.
   *
   * @return \Drupal\user\UserStorageInterface
   *   The user storage handler.
   */
  protected function getUserStorage(): UserStorageInterface {
    return $this->entityTypeManager->getStorage('user');
  }

}
