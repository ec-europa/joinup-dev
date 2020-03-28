<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_discussion\ExistingSite;

use Drupal\Tests\joinup_community_content\ExistingSite\CommunityContentWorkflowTestBase;
use Drupal\joinup_community_content\CommunityContentWorkflowAccessControlHandler;

/**
 * Tests CRUD operations and workflow transitions for the discussion node.
 *
 * @group joinup_discussion
 */
class DiscussionWorkflowTest extends CommunityContentWorkflowTestBase {

  /**
   * {@inheritdoc}
   */
  protected function getPublishedStates(): array {
    return ['validated', 'archived'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityBundle(): string {
    return 'discussion';
  }

  /**
   * {@inheritdoc}
   */
  protected function createAccessProvider(): array {
    $return = parent::createAccessProvider();
    foreach (['collection', 'solution'] as $bundle) {
      unset($return[$bundle][CommunityContentWorkflowAccessControlHandler::PRE_MODERATION]);
    }
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  protected function viewAccessProvider(): array {
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
  protected function updateAccessProvider(): array {
    $data = parent::updateAccessProvider();
    foreach (['collection', 'solution'] as $bundle) {
      unset($data[$bundle][CommunityContentWorkflowAccessControlHandler::PRE_MODERATION]);
      foreach (['userModerator', 'userOgFacilitator'] as $user) {
        $data[$bundle][CommunityContentWorkflowAccessControlHandler::POST_MODERATION]['validated']['any'][$user][] = 'archived';
      }
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  protected function deleteAccessProvider(): array {
    $data = parent::deleteAccessProvider();
    foreach (['collection', 'solution'] as $bundle) {
      unset($data[$bundle][CommunityContentWorkflowAccessControlHandler::PRE_MODERATION]);
      $data[$bundle][CommunityContentWorkflowAccessControlHandler::POST_MODERATION]['archived']['own'] = TRUE;
      $data[$bundle][CommunityContentWorkflowAccessControlHandler::POST_MODERATION]['archived']['any'] = [
        'userModerator',
        'userOgFacilitator',
      ];
    }

    return $data;
  }

}
