<?php

/**
 * @file
 * Contains \JoinupInviteSubContext.
 */

declare(strict_types = 1);

use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use Drupal\joinup\Traits\NodeTrait;
use Drupal\joinup\Traits\UserTrait;
use Drupal\joinup_invite\Entity\Invitation;
use Drupal\joinup_invite\Form\InviteToDiscussionForm;
use Drupal\joinup_invite\InvitationMessageHelperInterface;

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

    $invitation = Invitation::loadByEntityAndUser($discussion, $user, 'discussion');
    $arguments = $this->getInvitationMessageHelper()->getMessage($invitation, InviteToDiscussionForm::TEMPLATE_DISCUSSION_INVITE)->getArguments();

    $this->visitPath($arguments['@invitation:accept_url']);
  }

  /**
   * Returns the service that assists in working with messages for invitations.
   *
   * @return \Drupal\joinup_invite\InvitationMessageHelperInterface
   *   The service.
   */
  protected function getInvitationMessageHelper() : InvitationMessageHelperInterface {
    return \Drupal::service('joinup_invite.invitation_message_helper');
  }

}
