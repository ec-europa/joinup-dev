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
    $this->configFactory = \Drupal::service('config.factory');
    $this->ogAccess = \Drupal::service('og.access');
    $account = $account ?: \Drupal::currentUser();
    $comment_access_strict = $this->configFactory->get('og_comment.settings')->get('entity_access_strict');

    if ($operation === 'edit') {
      // Only users with administer comments permission can edit the comment
      // status field.
      $result = AccessResult::allowedIf($this->hasPermission('administer comments', $host_entity, $account));
      return $return_as_object ? $result : $result->isAllowed();
    }
    if ($operation === 'view') {
      // Only users with either post comments or access comments permisison can
      // view the field value. The formatter,
      // Drupal\comment\Plugin\Field\FieldFormatter\CommentDefaultFormatter,
      // takes care of showing the thread and form based on individual
      // permissions, so if a user only has ‘post comments’ access, only the
      // form will be shown and not the comments.
      $result = AccessResult::allowedIf($this->hasPermission('access comments', $host_entity, $account))
        ->orIf(AccessResult::allowedIf($this->hasPermission('post comments', $host_entity, $account)));
      return $return_as_object ? $result : $result->isAllowed();
    }

    if ($comment_access_strict) {
      return $return_as_object ? AccessResult::forbidden() : AccessResult::forbidden()->isAllowed();
    }
    return parent::access($operation, $account, $return_as_object);
  }

  /**
   * Returns whether the account has the given permission.
   *
   * The 'comment_access_strict' setting is taken into account.
   *
   * @param string $permission
   *   The permission to check.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The og group or group content object.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account object.
   *
   * @return bool
   *   Whether the user has the given permission.
   */
  protected function hasPermission($permission, EntityInterface $entity, AccountInterface $account) {
    $comment_access_strict = $this->configFactory->get('og_comment.settings')->get('entity_access_strict');
    $access = $entity->access($permission, $account);

    return $access || ($comment_access_strict && $account->hasPermission($permission));
  }

}
