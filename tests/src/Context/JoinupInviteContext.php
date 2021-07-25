<?php

declare(strict_types = 1);

namespace Drupal\joinup\Context;

use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\joinup\Traits\EntityTrait;
use Drupal\joinup\Traits\UserTrait;
use Drupal\joinup_discussion\Form\InviteToDiscussionForm;
use Drupal\joinup_invite\Entity\Invitation;
use Drupal\joinup_invite\Form\InviteToGroupForm;
use Drupal\joinup_invite\InvitationMessageHelperInterface;

/**
 * Behat step definitions for testing invitations.
 */
class JoinupInviteContext extends RawDrupalContext {

  use EntityTrait;
  use UserTrait;

  /**
   * Accepts or rejects an invitation.
   *
   * @param string $action
   *   The action being taken on the invitation, can be either 'accept' or
   *   'reject'.
   * @param string $title
   *   The title of the entity the user has been invited to.
   * @param string $bundle
   *   The bundle of the entity the user has been invited to.
   * @param string $type
   *   The type of the entity the user has been invited to. Either 'group' or
   *   'content'.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the action is anything other than 'accept' or 'reject'.
   *
   * @When I :action the invitation for the :title :bundle :type
   */
  public function acceptInvitation(string $action, string $title, string $bundle, string $type): void {
    if (!in_array($type, ['group', 'content'])) {
      throw new \InvalidArgumentException("Unknown type '$action'. Valid actions are 'group' and 'content'.");
    }
    if (!in_array($action, ['accept', 'reject'])) {
      throw new \InvalidArgumentException("Unknown action '$action'. Valid actions are 'accept' and 'reject'.");
    }

    $entity = $this->getEntityByLabel(($type === 'group' ? 'rdf_entity' : 'node'), $title, $bundle);
    $user = $this->userManager->getCurrentUser();
    $user = $this->getUserByName($user->name);

    $invitation_info = $this->getInvitationInfo($bundle);
    $invitation = Invitation::loadByEntityAndUser($entity, $user, $invitation_info['invitation_bundle']);
    $arguments = $this->getInvitationMessageHelper()->getMessage($invitation, $invitation_info['message_template'])->getArguments();

    $this->visitPath($arguments["@invitation:${action}_url"]);
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
      'collection' => [
        'invitation_bundle' => 'group_membership',
        'message_template' => InviteToGroupForm::TEMPLATES['community'],
      ],
      'solution' => [
        'invitation_bundle' => 'group_membership',
        'message_template' => InviteToGroupForm::TEMPLATES['solution'],
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
