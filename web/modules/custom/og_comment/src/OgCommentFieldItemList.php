<?php

namespace Drupal\og_comment;

use Drupal\comment\CommentFieldItemList;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines a item list class for comment fields.
 */
class OgCommentFieldItemList extends CommentFieldItemList {

  /**
   * The og access manager service.
   *
   * @var \Drupal\og\OgAccessInterface
   */
  protected $ogAccess;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function access($operation = 'view', AccountInterface $account = NULL, $return_as_object = FALSE) {
    $host_entity = $this->getEntity();
    // We cannot inject services in this plugin as it extends TypedData and it
    // does not support dependency injection.
    // @see https://www.drupal.org/node/2053415
    $this->configFactory = \Drupal::service('config.factory');
    $this->ogAccess = \Drupal::service('og.access');
    $account = $account ?: \Drupal::currentUser();
    $comment_access_strict = $this->configFactory->get('og_comment.settings')->get('comment_access_strict');

    if ($operation === 'edit') {
      // Only users with administer comments permission can edit the comment
      // status field.
      $result = $this->hasPermission('administer comments', $host_entity, $account);
      return $return_as_object ? $result : $result->isAllowed();
    }
    if ($operation === 'view') {
      // Only users with either post comments or access comments permission can
      // view the field value. The formatter,
      // Drupal\comment\Plugin\Field\FieldFormatter\CommentDefaultFormatter,
      // takes care of showing the thread and form based on individual
      // permissions, so if a user only has ‘post comments’ access, only the
      // form will be shown and not the comments.
      $has_access = $this->hasPermission('access comments', $host_entity, $account);
      $has_update = $this->hasPermission('post comments', $host_entity, $account);
      $result = ($has_access->isAllowed() || $has_update->isAllowed()) ? AccessResult::allowed() : AccessResult::forbidden();
      return $return_as_object ? $result : $result->isAllowed();
    }

    $result = $this->hasPermission($operation, $host_entity, $account);
    if (!$result->isNeutral() || $comment_access_strict) {
      return $return_as_object ? $result : $result->isAllowed();
    }

    // At this point the 'comment_access_strict' is false and the result is
    // neutral.
    return parent::access($operation, $account, $return_as_object);
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
  protected function hasPermission($permission, EntityInterface $entity, AccountInterface $account) {
    $access = $entity->access($permission, $account, TRUE);

    if (!$access->isNeutral()) {
      return $access;
    }

    // At this point and the group result is neutral.
    return AccessResult::allowedIf($account->hasPermission($permission));
  }

}
