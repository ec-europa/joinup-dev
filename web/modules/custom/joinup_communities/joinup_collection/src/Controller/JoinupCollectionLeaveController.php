<?php

declare(strict_types = 1);

namespace Drupal\joinup_collection\Controller;

use Drupal\collection\Form\LeaveCollectionConfirmForm;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\joinup_collection\JoinupCollectionHelper;
use Drupal\rdf_entity\RdfInterface;

/**
 * Provides an access controller for the 'collection.leave_confirm_form' route.
 */
class JoinupCollectionLeaveController {

  /**
   * Access check for the LeaveCollectionConfirmForm.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The collection that is on the verge of losing a member.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result object.
   */
  public static function access(RdfInterface $rdf_entity): AccessResultInterface {
    // The 'Joinup' membership can not be revoked.
    if ($rdf_entity->id() === JoinupCollectionHelper::getCollectionId()) {
      return AccessResult::forbidden();
    }
    return LeaveCollectionConfirmForm::access($rdf_entity);
  }

}
