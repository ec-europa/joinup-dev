<?php

namespace Drupal\joinup_collection\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\Plugin\Action\DeleteOgMembership;

/**
 * Extends the standard OG group membership deletion.
 *
 * @Action(
 *   id = "joinup_collection_og_membership_delete_action",
 *   label = @Translation("Delete the selected membership(s)"),
 *   type = "og_membership"
 * )
 */
class JoinupCollectionDeleteOgMembership extends DeleteOgMembership {

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\og\Entity\OgMembership $object */
    // 'Joinup' collection membership cannot be revoked.
    if ($object->getGroupId() === JOINUP_COLLECTION_ID) {
      return $return_as_object ? AccessResult::forbidden() : FALSE;
    }
    return parent::access($object, $account, $return_as_object);
  }

}
