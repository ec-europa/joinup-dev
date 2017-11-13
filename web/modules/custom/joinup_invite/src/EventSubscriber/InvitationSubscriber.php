<?php

declare(strict_types = 1);

namespace Drupal\joinup_invite\EventSubscriber;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Url;
use Drupal\joinup_core\JoinupRelationManager;
use Drupal\joinup_core\WorkflowHelperInterface;
use Drupal\joinup_invite\Controller\AcceptDiscussionInvitationController;
use Drupal\joinup_invite\Event\InviteToDiscussionEvent;
use Drupal\joinup_invite\InvitationEvents;
use Drupal\joinup_invite\InviteToDiscussionHelper;
use Drupal\joinup_notification\EventSubscriber\NotificationSubscriberBase;
use Drupal\message_notify\MessageNotifier;
use Drupal\node\NodeInterface;
use Drupal\og\GroupTypeManager;
use Drupal\og\MembershipManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscribers that send notifications for the Joinup Invite module.
 */
class InvitationSubscriber extends NotificationSubscriberBase implements EventSubscriberInterface {

  /**
   * The message template to use for the notification mail.
   *
   * @var string
   */
  const TEMPLATE_DISCUSSION_INVITE = 'discussion_invite';

  /**
   * The status value for an invitation that is pending.
   *
   * @var string
   */
  const INVITATION_STATUS_PENDING = 'pending';

  /**
   * The status value for an invitation that has been accepted.
   *
   * @var string
   */
  const INVITATION_STATUS_ACCEPTED = 'accepted';

  /**
   * The status value for an invitation that has been rejected.
   *
   * @var string
   */
  const INVITATION_STATUS_REJECTED = 'rejected';

  /**
   * The service that assists in inviting users to discussions.
   *
   * @var \Drupal\joinup_invite\InviteToDiscussionHelper
   */
  protected $inviteToDiscussionHelper;

  /**
   * Constructs a new InvitationSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Session\AccountProxy $current_user
   *   The current user service.
   * @param \Drupal\og\GroupTypeManager $og_group_type_manager
   *   The og group type manager service.
   * @param \Drupal\og\MembershipManagerInterface $og_membership_manager
   *   The og membership manager service.
   * @param \Drupal\joinup_core\WorkflowHelperInterface $joinup_core_workflow_helper
   *   The workflow helper service.
   * @param \Drupal\joinup_core\JoinupRelationManager $joinup_core_relations_manager
   *   The relation manager service.
   * @param \Drupal\message_notify\MessageNotifier $message_notifier
   *   The message notifier service.
   * @param \Drupal\joinup_invite\InviteToDiscussionHelper $invite_to_discussion_helper
   *   The service that assists in inviting users to discussions.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactory $config_factory, AccountProxy $current_user, GroupTypeManager $og_group_type_manager, MembershipManagerInterface $og_membership_manager, WorkflowHelperInterface $joinup_core_workflow_helper, JoinupRelationManager $joinup_core_relations_manager, MessageNotifier $message_notifier, InviteToDiscussionHelper $invite_to_discussion_helper) {
    parent::__construct($entity_type_manager, $config_factory, $current_user, $og_group_type_manager, $og_membership_manager, $joinup_core_workflow_helper, $joinup_core_relations_manager, $message_notifier);

    $this->inviteToDiscussionHelper = $invite_to_discussion_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() : array {
    return [InvitationEvents::INVITE_TO_DISCUSSION_EVENT => 'inviteUserToDiscussion'];
  }

  /**
   * Sends the notification to invite a user to participate in a discussion.
   *
   * @param \Drupal\joinup_invite\Event\InviteToDiscussionEvent $event
   *   The event object.
   *
   * @throws \Exception
   *   Thrown when a previous invitation with an unknown status has been
   *   encountered.
   */
  public function inviteUserToDiscussion(InviteToDiscussionEvent $event) : void {
    $result = [];

    $discussion = $event->getDiscussion();

    // Helper function that returns a single results array.
    $get_results_array = function (NodeInterface $discussion, AccountInterface $user) {
      return [
        'discussion' => $discussion->id(),
        'user' => $user->id(),
      ];
    };

    foreach ($event->getUsers() as $user) {
      // Check if a previous invitation already exists.
      $message = $this->inviteToDiscussionHelper->getInvitationMessage($discussion, $user);
      if (!empty($message)) {
        switch ($message->get('field_invitation_status')->value) {
          // If the invitation was already accepted, don't send an invitation.
          case self::INVITATION_STATUS_ACCEPTED:
            $result['accepted'][] = $get_results_array($discussion, $user);
            break;

          // If the invitation was already rejected, don't send an invitation.
          case self::INVITATION_STATUS_REJECTED:
            $result['rejected'][] = $get_results_array($discussion, $user);
            break;

          // If the invitation is still pending, resend the invitation.
          case self::INVITATION_STATUS_PENDING:
            $options = ['save on success' => FALSE, 'mail' => $user->getEmail()];
            $success = $this->messageNotifier->send($message, $options);
            $status = $success ? 'resent' : 'failed';
            $result[$status][] = $get_results_array($discussion, $user);
            break;

          default:
            throw new \Exception('Unknown invitation status');
        }
        continue;
      }

      $arguments = $this->generateArguments($discussion);

      // Generate the invitation link.
      $url_arguments = [
        'node' => $discussion->id(),
        'user' => $user->id(),
        'hash' => AcceptDiscussionInvitationController::generateHash($discussion, $user),
      ];
      $url_options = ['absolute' => TRUE];
      $arguments['@discussion:invite_url'] = Url::fromRoute('joinup_invite.discussion_accept', $url_arguments, $url_options)->toString();

      // Generate the message and send it.
      $values = ['template' => self::TEMPLATE_DISCUSSION_INVITE, 'arguments' => $arguments];
      /** @var \Drupal\message\MessageInterface $message */
      $message = $this->entityTypeManager->getStorage('message')->create($values);
      $message->set('field_invitation_discussion', $discussion->id());
      $message->set('field_invitation_user', $user->id());
      $options = ['save on success' => TRUE, 'mail' => $user->getEmail()];
      $success = $this->messageNotifier->send($message, $options);
      $status = $success ? 'success' : 'failed';
      $result[$status][] = $get_results_array($discussion, $user);
    }

    $event->setResult($result);
  }

}
