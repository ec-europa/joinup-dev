<?php

/**
 * @file
 * Contains \JoinupInviteSubContext.
 */

declare(strict_types = 1);

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use Drupal\joinup\Traits\NodeTrait;
use Drupal\joinup\Traits\RdfEntityTrait;
use Drupal\joinup\Traits\UserTrait;
use Drupal\joinup_discussion\Form\InviteToDiscussionForm;
use Drupal\joinup_invite\Entity\Invitation;
use Drupal\joinup_invite\Entity\InvitationInterface;
use Drupal\joinup_invite\Form\InviteToGroupForm;
use Drupal\joinup_invite\InvitationMessageHelperInterface;

/**
 * Behat step definitions for testing invitations.
 */
class JoinupInviteSubContext extends DrupalSubContextBase implements DrupalSubContextInterface {

  use NodeTrait;
  use UserTrait;
  use RdfEntityTrait;

  /**
   * Asserts that the user has an invitation in the given group.
   *
   * @param string $user
   *   The user name.
   * @param string $status
   *   The invitation status.
   * @param string $label
   *   The group label.
   * @param string $type
   *   The group type.
   *
   * @throws \Exception
   *   Thrown when an invalid status is requested or the requested invitation
   *   is not found.
   *
   * @Then :user should have a(n) :status invitation in the :label :type
   */
  public function userShouldBeInvitedToGroup(string $user, string $status, string $label, string $type): void {
    $statuses = [
      'accepted' => InvitationInterface::STATUS_ACCEPTED,
      'pending' => InvitationInterface::STATUS_PENDING,
    ];
    if (!isset($statuses[$status])) {
      throw new \Exception('Only the following statuses are allowed: ' . implode(', ', array_keys($statuses)));
    }
    $user = $this->getUserByName($user);
    $group = $this->getRdfEntityByLabel($label, $type);
    $invitation_info = $this->getInvitationInfo($type);
    $invitation = $this->loadInvitationUnchangedByEntityAndUser($group, $user, $invitation_info['invitation_bundle']);
    if (empty($invitation)) {
      throw new \Exception("No invitation was found for {$user->getAccountName()} in the '{$group->label()}' {$group->bundle()}");
    }
    PHPUnit_Framework_Assert::assertEquals($invitation->getStatus(), $statuses[$status]);
  }

  /**
   * Asserts that there is no invitation for the given user and group.
   *
   * @param string $user
   *   The user name.
   * @param string $label
   *   The group label.
   * @param string $type
   *   The group type.
   *
   * @throws \Exception
   *   Thrown when an invalid status is requested or the requested invitation
   *   is not found.
   *
   * @Then no invitation should exist for the user :user in the :label :type
   */
  public function assertNoInvitation(string $user, string $label, string $type): void {
    $user = $this->getUserByName($user);
    $group = $this->getRdfEntityByLabel($label, $type);
    $invitation_info = $this->getInvitationInfo($type);
    $invitation = Invitation::loadByEntityAndUser($group, $user, $invitation_info['invitation_bundle']);
    if (!empty($invitation)) {
      throw new \Exception("An invitation was found for {$user->getAccountName()} in the '{$group->label()}' {$group->bundle()}");
    }
  }

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
      throw new \InvalidArgumentException("Unknown action '{$action}'. Valid actions are 'accept' and 'reject'.");
    }
    $group = $this->getNodeByTitle($title, $type);
    $user = $this->userManager->getCurrentUser();
    $user = $this->getUserByName($user->name);

    $invitation_info = $this->getInvitationInfo($type);
    $invitation = $this->loadInvitationUnchangedByEntityAndUser($group, $user, $invitation_info['invitation_bundle']);
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
        'invitation_bundle' => 'group',
        'message_template' => InviteToGroupForm::TEMPLATE_GROUP_INVITE,
      ],
    ];

    return $mapping[$entity_type];
  }

  /**
   * Loads an unchanged invitation.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity related to the invitation.
   * @param \Drupal\Core\Session\AccountInterface $recipient
   *   The recipient related to the invitation.
   * @param string $bundle
   *   The bundle of the invitation.
   *
   * @return \Drupal\joinup_invite\Entity\InvitationInterface
   *   The invitation object.
   *
   * @throws \Exception
   *   Thrown when an invitation is not found.
   */
  protected function loadInvitationUnchangedByEntityAndUser(ContentEntityInterface $entity, AccountInterface $recipient, string $bundle = ''): ?InvitationInterface {
    $storage = \Drupal::entityTypeManager()->getStorage('invitation');
    $query = $storage->getQuery();
    $query->condition('entity_type', $entity->getEntityTypeId())
      ->condition('entity_id', $entity->id())
      ->condition('recipient_id', $recipient->id())
      ->condition('bundle', $bundle);
    $results = $query->execute();
    if (empty($results)) {
      throw new \Exception('No invitation was found.');
    }

    $iid = reset($results);
    /** @var \Drupal\joinup_invite\Entity\InvitationInterface $invitation */
    $invitation = $storage->loadUnchanged($iid);
    return $invitation;
  }

  /**
   * Searches the accept/reject invitation link from an email and clicks to it.
   *
   * @param string $action
   *   The action to be taken. Either accept or reject.
   * @param string $user
   *   The user name.
   *
   * @throws \Exception
   *   Thrown when a user, the email, or the link is not.
   *
   * @When I click the :action invitation link from the last email sent to :user
   */
  public function clickDeleteConfirmationLink(string $action, string $user): void {
    $user = $this->getUserByName($user);
    $user_email = $user->getEmail();

    \Drupal::state()->resetCache();
    $mails = \Drupal::state()->get('system.test_mail_collector');
    if (empty($mails)) {
      throw new \Exception('No mail was sent.');
    }

    $link = NULL;
    $matches = [];
    foreach ($mails as $mail) {
      if ($mail['to'] !== $user_email) {
        continue;
      }

      $pattern = '#href="(?<action_url>[^"]*?\/' . $action . '\/.*?)"#';
      $body = (string) $mail['body'];
      preg_match_all($pattern, $body, $matches);
      $matches = array_filter($matches);
      // $matches[0] are urls with the characters outside the matching
      // parenthesis. $matches[1] are the matches restricted by the parenthesis
      // tags in the regex. $matches[1] contain the clean urls.
      if (empty($matches['action_url'])) {
        continue;
      }
      break;
    }

    if (empty($matches)) {
      $message = t("The @action invitation url was not found in the mail body: \n@body", [
        '@action' => $action,
        '@body' => $body,
      ]);
      throw new \Exception($message);
    }
    $url = reset($matches['action_url']);
    $this->visitPath($url);
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
