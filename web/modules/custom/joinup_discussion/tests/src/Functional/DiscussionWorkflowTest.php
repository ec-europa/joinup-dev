<?php

namespace Drupal\Tests\joinup_discussion\Functional;

use Drupal\Tests\joinup_core\Functional\NodeWorkflowTestBase;

/**
 * Tests CRUD operations and workflow transitions for the discussion node.
 *
 * @group workflow
 */
class DiscussionWorkflowTest extends NodeWorkflowTestBase {

  /**
   * {@inheritdoc}
   */
  protected function getPublishedStates() {
    return ['validated', 'archived'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityBundle() {
    return 'discussion';
  }

  /**
   * {@inheritdoc}
   */
  protected function createAccessProvider() {
    $return = parent::createAccessProvider();
    foreach (['collection', 'solution'] as $bundle) {
      unset($return[$bundle][self::PRE_MODERATION]);
    }
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  protected function viewAccessProvider() {
    $data = parent::viewAccessProvider();
    foreach (['collection', 'solution'] as $bundle) {
      $data[$bundle]['archived']['own'] = TRUE;
      $data[$bundle]['archived']['any'] = [
        'userAnonymous',
        'userAuthenticated',
        'userModerator',
        'userOgMember',
        'userOgFacilitator',
        'userOgAdministrator',
      ];
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  protected function updateAccessProvider() {
    $data = parent::updateAccessProvider();
    foreach (['collection', 'solution'] as $bundle) {
      foreach ([self::PRE_MODERATION, self::POST_MODERATION] as $moderation) {
        foreach (['userModerator', 'userOgFacilitator'] as $user) {
          $data[$bundle][$moderation]['published']['any'][$user][] = 'disable';
        }
      }
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  protected function deleteAccessProvider() {
    $data = parent::deleteAccessProvider();
    foreach (['collection', 'solution'] as $bundle) {
      foreach ([self::PRE_MODERATION, self::POST_MODERATION] as $moderation) {
        $data[$bundle][$moderation]['archived']['own'] = TRUE;
        $data[$bundle][$moderation]['archived']['any'] = [
          'userModerator',
          'userOgFacilitator',
        ];
      }
    }

    return $data;
  }

}
