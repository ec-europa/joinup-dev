<?php

declare(strict_types = 1);

namespace Drupal\joinup_group\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\joinup_group\Entity\GroupInterface;

/**
 * Returns responses for the group reports page.
 *
 * This is a page containing various reports about a group and is accessible for
 * facilitators and moderators. The reports are discovered through an event.
 *
 * This page can be reached through the three-dots menu on the group overview.
 */
class GroupReportsController extends ControllerBase {

  /**
   * Renders the group reports page.
   *
   * @param \Drupal\joinup_group\Entity\GroupInterface $rdf_entity
   *   The group for which to build the reports page.
   *
   * @return array
   *   The page as a render array.
   */
  public function reports(GroupInterface $rdf_entity): array {
    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('To be implemented'),
    ];

    return $build;
  }

  /**
   * Access check for the group reports page.
   *
   * Only facilitators and moderators have the required 'access group reports'
   * permission.
   *
   * @param \Drupal\joinup_group\Entity\GroupInterface $rdf_entity
   *   The group for which the access to the reports page is being determined.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result object.
   */
  public function access(GroupInterface $rdf_entity): AccessResultInterface {
    $user = $this->currentUser();

    // Check if the user has the global permission to access group reports of
    // all groups (e.g. moderators).
    if ($user->hasPermission('access group reports')) {
      return AccessResult::allowed();
    }

    // Check if the user has permission to access the group reports page of this
    // particular group (e.g. facilitators).
    return $rdf_entity->getGroupAccess('access group reports', $user);
  }

}
