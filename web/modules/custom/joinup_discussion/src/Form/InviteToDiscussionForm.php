<?php

declare(strict_types = 1);

namespace Drupal\joinup_discussion\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\joinup_group\JoinupGroupHelper;
use Drupal\joinup_invite\Entity\Invitation;
use Drupal\joinup_invite\Entity\InvitationInterface;
use Drupal\joinup_invite\Form\InviteFormBase;
use Drupal\joinup_invite\InvitationMessageHelperInterface;
use Drupal\joinup_notification\MessageArgumentGenerator;
use Drupal\joinup_subscription\JoinupDiscussionSubscriptionInterface;
use Drupal\node\NodeInterface;
use Drupal\og\OgAccessInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Form to invite users to participate in a discussion.
 */
class InviteToDiscussionForm extends InviteFormBase {

  /**
   * The message template to use for the notification mail.
   *
   * @var string
   */
  const TEMPLATE_DISCUSSION_INVITE = 'discussion_invite';

  /**
   * The value indicating a successful invitation.
   *
   * @var string
   */
  const RESULT_SUCCESS = 'success';

  /**
   * The value indicating a failed invitation.
   *
   * @var string
   */
  const RESULT_FAILED = 'failed';

  /**
   * The value indicating an invitation that has been resent.
   *
   * @var string
   */
  const RESULT_RESENT = 'resent';

  /**
   * The value indicating an invitation that has been previously accepted.
   *
   * @var string
   */
  const RESULT_ACCEPTED = 'accepted';

  /**
   * The value indicating an invitation that has been previously rejected.
   *
   * @var string
   */
  const RESULT_REJECTED = 'rejected';

  /**
   * The severity of the messages displayed to the user, keyed by result type.
   *
   * @var string[]
   */
  const INVITATION_MESSAGE_TYPES = [
    self::RESULT_SUCCESS => 'status',
    self::RESULT_FAILED => 'error',
    self::RESULT_RESENT => 'status',
    self::RESULT_ACCEPTED => 'status',
    self::RESULT_REJECTED => 'status',
  ];

  /**
   * The discussion node where to invite users.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $discussion;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The helper service for creating and retrieving messages for invitations.
   *
   * @var \Drupal\joinup_invite\InvitationMessageHelperInterface
   */
  protected $invitationMessageHelper;

  /**
   * The subscription service.
   *
   * @var \Drupal\joinup_subscription\JoinupDiscussionSubscriptionInterface
   */
  protected $subscription;

  /**
   * The OG access service.
   *
   * @var \Drupal\og\OgAccessInterface
   */
  protected $ogAccess;

  /**
   * Constructs a new InviteToDiscussionForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   * @param \Drupal\joinup_invite\InvitationMessageHelperInterface $invitationMessageHelper
   *   The helper service for creating messages for invitations.
   * @param \Drupal\joinup_subscription\JoinupDiscussionSubscriptionInterface $subscription
   *   The subscription service.
   * @param \Drupal\og\OgAccessInterface $ogAccess
   *   The OG access service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, EventDispatcherInterface $eventDispatcher, InvitationMessageHelperInterface $invitationMessageHelper, JoinupDiscussionSubscriptionInterface $subscription, OgAccessInterface $ogAccess) {
    parent::__construct($entityTypeManager);

    $this->eventDispatcher = $eventDispatcher;
    $this->invitationMessageHelper = $invitationMessageHelper;
    $this->subscription = $subscription;
    $this->ogAccess = $ogAccess;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('event_dispatcher'),
      $container->get('joinup_invite.invitation_message_helper'),
      $container->get('joinup_subscription.discussion_subscription'),
      $container->get('og.access')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'invite_to_discussion_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getSubmitButtonText(): TranslatableMarkup {
    return $this->t('Invite to discussion');
  }

  /**
   * {@inheritdoc}
   */
  protected function getCancelButtonUrl(): Url {
    return new Url('entity.node.canonical', [
      'node' => $this->discussion->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?NodeInterface $node = NULL) {
    $this->discussion = $node;

    return parent::build($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\user\UserInterface[] $users */
    $users = $this->getUserList($form_state);
    $results = array_fill_keys(array_keys(self::INVITATION_MESSAGE_TYPES), 0);

    foreach ($users as $user) {
      // Check if the user is already subscribed to the discussion. In this case
      // no invitation needs to be sent.
      if ($this->subscription->isSubscribed($user, $this->discussion, 'subscribe_discussions')) {
        $results[self::RESULT_ACCEPTED]++;
        continue;
      }

      // Check if a previous invitation already exists.
      $invitation = Invitation::loadByEntityAndUser($this->discussion, $user, 'discussion');
      if (!empty($invitation)) {
        switch ($invitation->getStatus()) {
          // If the invitation was already accepted, don't send an invitation.
          case InvitationInterface::STATUS_ACCEPTED:
            $results[self::RESULT_ACCEPTED]++;
            break;

          // If the invitation was already rejected, don't send an invitation.
          case InvitationInterface::STATUS_REJECTED:
            $results[self::RESULT_REJECTED]++;
            break;

          // If the invitation is still pending, resend the invitation.
          case InvitationInterface::STATUS_PENDING:
            $success = $this->sendMessage($invitation);
            $status = $success ? self::RESULT_RESENT : self::RESULT_FAILED;
            $results[$status]++;
            break;

          default:
            throw new \Exception('Unknown invitation status: "' . $invitation->getStatus() . '".');
        }
        continue;
      }

      // No previous invitation exists. Create it.
      /** @var \Drupal\joinup_invite\Entity\InvitationInterface $invitation */
      $invitation = $this->entityTypeManager->getStorage('invitation')->create(['bundle' => 'discussion']);
      $invitation
        ->setRecipient($user)
        ->setEntity($this->discussion)
        ->save();

      // Send the notification message for the invitation.
      $success = $this->sendMessage($invitation);
      $status = $success ? 'success' : 'failed';
      $results[$status]++;
    }

    // Display status messages.
    foreach (array_filter($results) as $result => $count) {
      $type = self::INVITATION_MESSAGE_TYPES[$result];
      $args = [':count' => $count];
      switch ($result) {
        case self::RESULT_SUCCESS:
          $message = $this->t(':count user(s) have been invited to this discussion.', $args);
          break;

        case self::RESULT_FAILED:
          $message = $this->t('The invitation could not be sent for :count user(s). Please try again later.', $args);
          break;

        case self::RESULT_RESENT:
          $message = $this->t("The invitation was resent to :count user(s) that were already invited previously but haven't yet accepted the invitation.", $args);
          break;

        case self::RESULT_ACCEPTED:
          $message = $this->t(':count user(s) were already subscribed to the discussion. No new invitation was sent.', $args);
          break;

        case self::RESULT_REJECTED:
          $message = $this->t(':count user(s) have previously rejected the invitation. No new invitation was sent.', $args);
          break;

        default:
          throw new \Exception("Unknown result type '$result'.");
      }
      $this->messenger()->addMessage($message, $type);
    }
  }

  /**
   * Access check for the form.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The user for which to check access.
   * @param \Drupal\node\NodeInterface|null $node
   *   The discussion to which users will be invited.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result object.
   */
  public function access(AccountProxyInterface $account, ?NodeInterface $node = NULL): AccessResult {
    $access = FALSE;

    // The node should be a published discussion.
    if ($node->bundle() === 'discussion' && $node->isPublished()) {
      // Only allow access if the current user is a group administrator (a.k.a.
      // the user is a moderator), has permission to invite users to discussions
      // in the solution or collection that contains the discussion (a.k.a. the
      // user is a facilitator), or the author of the discussion itself.
      $user = $account->getAccount();
      /** @var \Drupal\rdf_entity\Entity\Rdf $group */
      $group = JoinupGroupHelper::getGroup($node);

      $is_group_administrator = $user->hasPermission('administer organic groups');
      $is_owner = $user->id() == $node->getOwnerId();
      $is_discussion_inviter = $this->ogAccess->userAccess($group, 'invite users to discussions')->isAllowed();

      $access = $is_group_administrator || $is_owner || $is_discussion_inviter;
    }

    return AccessResult::allowedIf($access);
  }

  /**
   * Sends a new message to invite the given user to the given discussion.
   *
   * @param \Drupal\joinup_invite\Entity\InvitationInterface $invitation
   *   The invitation.
   *
   * @return bool
   *   Whether or not the message was successfully delivered.
   */
  protected function sendMessage(InvitationInterface $invitation): bool {
    $arguments = $this->generateArguments($invitation->getEntity());
    $message = $this->invitationMessageHelper->createMessage($invitation, self::TEMPLATE_DISCUSSION_INVITE, $arguments);
    $message->save();

    return $this->invitationMessageHelper->sendMessage($invitation, self::TEMPLATE_DISCUSSION_INVITE);
  }

  /**
   * Returns the arguments for an invitation message.
   *
   * @param \Drupal\Core\Entity\EntityInterface $discussion
   *   The discussion for which to generate the message arguments.
   *
   * @return array
   *   The message arguments.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   *   Thrown when the first name or last name of the current user is not known.
   * @throws \Drupal\Core\Entity\EntityMalformedException
   *   Thrown when the URL for the discussion cannot be generated.
   */
  protected function generateArguments(EntityInterface $discussion): array {
    $arguments = [];

    $arguments['@entity:title'] = $discussion->label();
    $arguments['@entity:url'] = $discussion->toUrl('canonical', ['absolute' => TRUE])->toString();

    $arguments += MessageArgumentGenerator::getActorArguments();

    return $arguments;
  }

}
