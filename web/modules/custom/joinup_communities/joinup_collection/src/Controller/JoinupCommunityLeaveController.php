<?php

declare(strict_types = 1);

namespace Drupal\joinup_collection\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\joinup_collection\JoinupCommunityHelper;
use Drupal\joinup_group\Form\LeaveGroupConfirmForm;
use Drupal\rdf_entity\RdfInterface;

/**
 * Provides an access controller for the 'collection.leave_confirm_form' route.
 */
class JoinupCommunityLeaveController {

  /**
   * Access check for the LeaveCommunityConfirmForm.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The collection that is on the verge of losing a member.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result object.
   */
  public static function access(RdfInterface $rdf_entity): AccessResultInterface {
    // The 'Joinup' membership can not be revoked.
    if ($rdf_entity->id() === JoinupCommunityHelper::getCommunityId()) {
      return AccessResult::forbidden();
    }
    return LeaveGroupConfirmForm::access($rdf_entity);
  }

}
