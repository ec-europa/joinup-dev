<?php

declare(strict_types = 1);

/**
 * @file
 * Contains \JoinupInviteSubContext.
 */

use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use Drupal\joinup\Traits\NodeTrait;
use Drupal\joinup\Traits\UserTrait;
use Drupal\joinup_invite\InviteToDiscussionHelper;

/**
 * Behat step definitions for testing invitations.
 */
class JoinupInviteSubContext extends DrupalSubContextBase implements DrupalSubContextInterface {

  use NodeTrait;
  use UserTrait;

  /**
   * Navigates to the canonical page display of a event.
   *
   * @param string $title
   *   The name of the discussion.
   *
   * @When I accept the invitation to participate in the :title discussion
   */
  public function acceptDiscussionInvitation(string $title) : void {
    $discussion = $this->getNodeByTitle($title, 'discussion');
    $user = $this->userManager->getCurrentUser();
    $user = $this->getUserByName($user->name);

    $discussion_helper = $this->getDiscussionHelper();
    $message = $discussion_helper->getInvitationMessage($discussion, $user);
    $arguments = $message->getArguments();

    $this->visitPath($arguments['@discussion:invite_url']);
  }

  /**
   * Returns the service that assists in inviting users to discussions.
   *
   * @return \Drupal\joinup_invite\InviteToDiscussionHelper
   *   The service.
   */
  protected function getDiscussionHelper() : InviteToDiscussionHelper {
    return \Drupal::service('joinup_invite.discussion_helper');
  }

}
