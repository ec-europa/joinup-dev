<?php

declare(strict_types = 1);

namespace Drupal\og_comment;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\comment\CommentAccessControlHandler;
use Drupal\og\OgAccessInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the access control handler for the comment entity type.
 *
 * @see \Drupal\comment\Entity\Comment
 */
class OgCommentAccessControlHandler extends CommentAccessControlHandler implements EntityHandlerInterface {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The OG access service.
   *
   * @var \Drupal\og\OgAccessInterface
   */
  protected $ogAccess;

  /**
   * Constructs the access handler class for the og comment.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The comment entity type object.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\og\OgAccessInterface $og_access
   *   The OG access service.
   */
  public function __construct(EntityTypeInterface $entity_type, ConfigFactoryInterface $config_factory, OgAccessInterface $og_access) {
    parent::__construct($entity_type);
    $this->configFactory = $config_factory;
    $this->ogAccess = $og_access;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('config.factory'),
      $container->get('og.access')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $comment_access_strict = $this->configFactory->get('og_comment.settings')->get('entity_access_strict');
    $host_entity = $entity->getCommentedEntity();
    $comment_admin = $this->hasPermission('administer comments', $host_entity, $account)->isAllowed();
    if ($operation == 'approve') {
      return AccessResult::allowedIf($comment_admin && !$entity->isPublished())
        ->cachePerPermissions()
        ->addCacheableDependency($entity);
    }

    if ($comment_admin) {
      $access = AccessResult::allowed()->cachePerPermissions();
      return ($operation != 'view') ? $access : $access->andIf($host_entity->access($operation, $account, TRUE));
    }

    switch ($operation) {
      case 'view':
        $user_permission = $this->hasPermission('access comments', $host_entity, $account)->isAllowed();
        $return = AccessResult::allowedIf($user_permission && $entity->isPublished())->cachePerPermissions()->addCacheableDependency($entity);
        break;

      case 'update':
        $return = AccessResult::allowedIf($account->id() && $account->id() == $entity->getOwnerId() && $entity->isPublished() && $this->hasPermission('edit own comments', $host_entity, $account)->isAllowed())->cachePerPermissions()->cachePerUser()->addCacheableDependency($entity);
        break;

      case 'delete':
        $return = AccessResult::allowedIf($account->id() && $account->id() == $entity->getOwnerId() && $entity->isPublished() && $this->hasPermission('delete own comments', $host_entity, $account)->isAllowed())->cachePerPermissions()->cachePerUser()->addCacheableDependency($entity);
        break;

      default:
        // No opinion.
        return AccessResult::neutral()->cachePerPermissions();

    }

    if (!$comment_access_strict) {
      $override = parent::checkAccess($entity, $operation, $account);
      return $return->orIf($override);
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    $commented_entity = !empty($context['commented_entity']) ? $context['commented_entity'] : NULL;
    $has_permission = $this->hasPermission('post comment', $commented_entity, $account);
    return $has_permission->isAllowed() ? AccessResult::allowed() : AccessResult::forbidden();
  }

  /**
   * Returns whether the account has the given permission.
   *
   * @param string $permission
   *   The permission to check.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The commented entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account object.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result object.
   */
  protected function hasPermission(string $permission, EntityInterface $entity, AccountInterface $account): AccessResultInterface {
    $access = $this->ogAccess->userAccessEntity($permission, $entity, $account);

    if ($access->isAllowed()) {
      return $access;
    }

    // The user doesn't have permission in the context of the group content.
    // Check if they have the permission globally.
    return AccessResult::allowedIf($account->hasPermission($permission));
  }

}
