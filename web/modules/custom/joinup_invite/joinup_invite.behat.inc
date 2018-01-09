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
use Drupal\joinup_discussion\Form\InviteToDiscussionForm;
use Drupal\joinup_invite\Entity\Invitation;
use Drupal\joinup_invite\InvitationMessageHelperInterface;

/**
 * Behat step definitions for testing invitations.
 */
class JoinupInviteSubContext extends DrupalSubContextBase implements DrupalSubContextInterface {

  use NodeTrait;
  use UserTrait;

  /**
   * Accepts or rejects an invitation.
   *
   * @param string $action
   *   The action being taken on the invitation, can be either 'accept' or
   *   'reject'.
   * @param string $title
   *   The title of the entity the user has been invited to.
   * @param string $type
   *   The type of the entity the user has been invited to.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the action is anything other than 'accept' or 'reject'.
   *
   * @When I :action the invitation for the :title :type
   */
  public function acceptInvitation(string $action, string $title, string $type): void {
    if (!in_array($action, ['accept', 'reject'])) {
      throw new \InvalidArgumentException("Unknown action '$action'. Valid actions are 'accept' and 'reject'.");
    }
    $entity = $this->getNodeByTitle($title, $type);
    $user = $this->userManager->getCurrentUser();
    $user = $this->getUserByName($user->name);

    $invitation_info = $this->getInvitationInfo($type);
    $invitation = Invitation::loadByEntityAndUser($entity, $user, $invitation_info['invitation_bundle']);
    $arguments = $this->getInvitationMessageHelper()->getMessage($invitation, $invitation_info['message_template'])->getArguments();

    $this->visitPath($arguments["@invitation:${action}_url"]);
  }

  /**
   * Removes a user from the list of users to invite.
   *
   * @param string $username
   *   The name of the user to remove.
   *
   * @throws \Exception
   *   When the button to remove the user is not found.
   *
   * @When I remove the user :username from the invitation list
   */
  public function removeUserFromInvitationList($username): void {
    // Find the element that contains the user name.
    $xpath = "//span[contains(concat(' ', normalize-space(@class), ' '), ' users-list__label ')][contains(text(), '$username')]"
      // Find the related button.
      . "/following-sibling::input[@type='submit'][@value='Remove']";
    $button = $this->getSession()->getPage()->find('xpath', $xpath);

    if (!$button) {
      throw new \Exception("Couldn't find the button to remove the user '$username'.");
    }

    $button->press();
  }

  /**
   * Returns information about the invitations related to the given entity type.
   *
   * @param string $entity_type
   *   The entity type for which to return relevant invitation info.
   *
   * @return array
   *   An associative array with the following keys:
   *   - invitation_bundle: The ID of the InvitationType that is associated with
   *     inviting people to content of the given entity type.
   *   - message_template: The message template which is used to generate
   *     invitation notifications when people are invited to content of the
   *     given entity type.
   */
  protected function getInvitationInfo(string $entity_type): array {
    $mapping = [
      'discussion' => [
        'invitation_bundle' => 'discussion',
        'message_template' => InviteToDiscussionForm::TEMPLATE_DISCUSSION_INVITE,
      ],
    ];

    return $mapping[$entity_type];
  }

  /**
   * Returns the service that assists in working with messages for invitations.
   *
   * @return \Drupal\joinup_invite\InvitationMessageHelperInterface
   *   The service.
   */
  protected function getInvitationMessageHelper(): InvitationMessageHelperInterface {
    return \Drupal::service('joinup_invite.invitation_message_helper');
  }

}
