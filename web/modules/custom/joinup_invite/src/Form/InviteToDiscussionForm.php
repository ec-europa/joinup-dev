<?php

declare(strict_types = 1);

namespace Drupal\joinup_invite\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\joinup_invite\Event\InviteToDiscussionEvent;
use Drupal\joinup_invite\InvitationEvents;
use Drupal\node\NodeInterface;
use Drupal\og\OgRoleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Form to invite users to participate in a discussion.
 */
class InviteToDiscussionForm extends InviteFormBase {

  const INVITATION_MESSAGE_TEXT = [
    InviteToDiscussionEvent::RESULT_SUCCESS => ':count user(s) have been invited to this discussion.',
    InviteToDiscussionEvent::RESULT_FAILED => 'The invitation could not be sent for :count user(s). Please try again later.',
    InviteToDiscussionEvent::RESULT_RESENT => 'The invitation was resent to :count user(s) that were already invited previously but haven\'t yet accepted the invitation.',
    InviteToDiscussionEvent::RESULT_ACCEPTED => ':count user(s) were already subscribed. No new invitation was sent.',
    InviteToDiscussionEvent::RESULT_REJECTED => ':count user(s) have previously rejected the invitation. No new invitation was sent.',
  ];

  const INVITATION_MESSAGE_TYPE = [
    InviteToDiscussionEvent::RESULT_SUCCESS => 'status',
    InviteToDiscussionEvent::RESULT_FAILED => 'error',
    InviteToDiscussionEvent::RESULT_RESENT => 'status',
    InviteToDiscussionEvent::RESULT_ACCEPTED => 'status',
    InviteToDiscussionEvent::RESULT_REJECTED => 'status',
  ];

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a new InviteToDiscussionForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EventDispatcherInterface $event_dispatcher) {
    parent::__construct($entity_type_manager);

    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('event_dispatcher')
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
  protected function getSubmitButtonText() : TranslatableMarkup {
    return $this->t('Invite to discussion');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $node = NULL) {
    $form_state->set('discussion', $node);
    return parent::build($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $user_ids = array_filter($form_state->getValue('users'));
    $users = $this->entityTypeManager->getStorage('user')->loadMultiple($user_ids);
    $discussion = $form_state->get('discussion');

    $event = new InviteToDiscussionEvent($users, $discussion);
    $this->eventDispatcher->dispatch(InvitationEvents::INVITE_TO_DISCUSSION_EVENT, $event);

    foreach ($event->getResult() as $type => $results) {
      $message = self::INVITATION_MESSAGE_TEXT[$type];
      $message_type = self::INVITATION_MESSAGE_TYPE[$type];
      // Coder complains about passing variables to the translation service, but
      // these are actually coming from a constant so it's fine.
      // @codingStandardsIgnoreLine
      drupal_set_message($this->t($message, [
        ':count' => count($results),
      ]), $message_type);
    }
  }

  /**
   * Access check for the form.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The user for which to check access.
   * @param \Drupal\node\NodeInterface $node
   *   The discussion to which users will be invited.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result object.
   */
  public static function access(AccountProxyInterface $account, NodeInterface $node = NULL) : AccessResult {
    $access = FALSE;

    // The node should be a discussion.
    if ($node->bundle() === 'discussion') {
      // Only allow access if the current user is a moderator, a facilitator of
      // the solution or collection that contains the discussion, or the author
      // of the discussion itself.
      $user = $account->getAccount();
      /** @var \Drupal\rdf_entity\Entity\Rdf $group */
      $group = \Drupal::service('joinup_core.relations_manager')->getParent($node);
      /** @var \Drupal\og\OgMembershipInterface $membership */
      $membership = \Drupal::service('og.membership_manager')->getMembership($group, $user);

      $is_moderator = in_array('moderator', $user->getRoles());
      $is_owner = $user->id() == $node->getOwnerId();
      $is_facilitator = !empty($membership) && (bool) array_filter($membership->getRoles(), function (OgRoleInterface $role) {
        return $role->getName() === 'facilitator';
      });

      $access = $is_moderator || $is_owner || $is_facilitator;
    }

    return AccessResult::allowedIf($access);
  }

}
