<?php

declare(strict_types = 1);

namespace Drupal\joinup_community_content;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\comment\CommentAccessControlHandler;
use Drupal\joinup_community_content\Entity\CommunityContentInterface;
use Drupal\og\OgAccessInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an access control handler for community content comment entities.
 */
class JoinupCommunityContentCommentAccessControlHandler extends CommentAccessControlHandler implements EntityHandlerInterface {

  /**
   * The OG access service.
   *
   * @var \Drupal\og\OgAccessInterface
   */
  protected $ogAccess;

  /**
   * Constructs a new access control handler instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The comment entity type object.
   * @param \Drupal\og\OgAccessInterface $og_access
   *   The OG access service.
   */
  public function __construct(EntityTypeInterface $entity_type, OgAccessInterface $og_access) {
    parent::__construct($entity_type);
    $this->ogAccess = $og_access;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type): self {
    return new static(
      $entity_type,
      $container->get('og.access')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $comment, $operation, AccountInterface $account): AccessResultInterface {
    $community_content = $comment->getCommentedEntity();
    if (!$community_content instanceof CommunityContentInterface) {
      return parent::checkAccess($comment, $operation, $account);
    }
    $is_comment_admin = $this->isAllowedIfHasPermission('administer comments', $community_content, $account);

    if ($operation == 'approve') {
      return AccessResult::allowedIf($is_comment_admin && !$comment->isPublished())
        ->cachePerPermissions()
        ->addCacheableDependency($comment);
    }

    if ($is_comment_admin) {
      $access = AccessResult::allowed()->cachePerPermissions();
      return ($operation != 'view') ? $access : $access->andIf($community_content->access($operation, $account, TRUE));
    }

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIf(
          $this->isAllowedIfHasPermission('access comments', $community_content, $account)
          && $comment->isPublished()
        )
          ->cachePerPermissions()
          ->addCacheableDependency($comment);

      case 'update':
        return AccessResult::allowedIf(
          $account->id() === $comment->getOwnerId()
          && $comment->isPublished()
          && $this->isAllowedIfHasPermission('edit own comments', $community_content, $account)
        )
          ->cachePerUser()
          ->addCacheableDependency($comment);

      case 'delete':
        return AccessResult::allowedIf(
          $account->id() === $comment->getOwnerId()
          && $comment->isPublished()
          && $this->isAllowedIfHasPermission('delete own comments', $community_content, $account)
        )
          ->cachePerUser()
          ->addCacheableDependency($comment);
    }

    // No opinion.
    return AccessResult::neutral()->cachePerPermissions();
  }

  /**
   * Checks whether the account has the given permission.
   *
   * This method check first the site-wide permission, as that always takes
   * precedent over the organic group permission.
   *
   * @param string $permission
   *   The permission to check.
   * @param \Drupal\joinup_community_content\Entity\CommunityContentInterface $community_content
   *   The commented entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account object.
   *
   * @return bool
   *   Whether the account has the given permission.
   */
  protected function isAllowedIfHasPermission(string $permission, CommunityContentInterface $community_content, AccountInterface $account): bool {
    // Has site-wide permission.
    return $account->hasPermission($permission)
      // Or organic groups permission.
      || $this->ogAccess->userAccess($community_content->getGroup(), $permission, $account)->isAllowed();
  }

}
