<?php

declare(strict_types = 1);

namespace Drupal\joinup_discussion\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\joinup_discussion\Entity\DiscussionInterface;
use Drupal\joinup_invite\Form\InviteFormBase;
use Drupal\joinup_invite\InvitationMessageHelperInterface;
use Drupal\joinup_subscription\JoinupDiscussionSubscriptionInterface;
use Drupal\node\NodeInterface;
use Drupal\og\OgAccessInterface;
use Drupal\user\UserInterface;
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
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

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
   * @param \Drupal\joinup_invite\InvitationMessageHelperInterface $invitationMessageHelper
   *   The helper service for creating messages for invitations.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   * @param \Drupal\joinup_subscription\JoinupDiscussionSubscriptionInterface $subscription
   *   The subscription service.
   * @param \Drupal\og\OgAccessInterface $ogAccess
   *   The OG access service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, InvitationMessageHelperInterface $invitationMessageHelper, EventDispatcherInterface $eventDispatcher, JoinupDiscussionSubscriptionInterface $subscription, OgAccessInterface $ogAccess) {
    parent::__construct($entityTypeManager, $invitationMessageHelper);

    $this->eventDispatcher = $eventDispatcher;
    $this->subscription = $subscription;
    $this->ogAccess = $ogAccess;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('joinup_invite.invitation_message_helper'),
      $container->get('event_dispatcher'),
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
      'node' => $this->entity->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?NodeInterface $node = NULL) {
    $this->entity = $node;
    return parent::build($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function processUser(UserInterface $user): string {
    // Check if the user is already subscribed to the discussion. In this case
    // no invitation needs to be sent.
    if ($this->subscription->isSubscribed($user, $this->entity, 'subscribe_discussions')) {
      return self::RESULT_ACCEPTED;
    }

    return parent::processUser($user);
  }

  /**
   * {@inheritdoc}
   */
  protected function processResults(array $results): void {
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
    if ($node instanceof DiscussionInterface && $node->isPublished()) {
      // Only allow access if the current user is a group administrator (a.k.a.
      // the user is a moderator), has permission to invite users to discussions
      // in the solution or collection that contains the discussion (a.k.a. the
      // user is a facilitator), or the author of the discussion itself.
      $user = $account->getAccount();
      $group = $node->getGroup();

      $is_group_administrator = $user->hasPermission('administer organic groups');
      $is_owner = $user->id() == $node->getOwnerId();
      $is_discussion_inviter = $this->ogAccess->userAccess($group, 'invite users to discussions')->isAllowed();

      $access = $is_group_administrator || $is_owner || $is_discussion_inviter;
    }

    return AccessResult::allowedIf($access);
  }

  /**
   * {@inheritdoc}
   */
  protected function getTemplateId(): string {
    return self::TEMPLATE_DISCUSSION_INVITE;
  }

  /**
   * {@inheritdoc}
   */
  protected function getInvitationType(): string {
    return 'discussion';
  }

}
