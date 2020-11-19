<?php

declare(strict_types = 1);

namespace Drupal\joinup_invite\Form;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\joinup_invite\Entity\Invitation;
use Drupal\joinup_invite\Entity\InvitationInterface;
use Drupal\joinup_invite\InvitationMessageHelperInterface;
use Drupal\joinup_notification\MessageArgumentGenerator;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for forms that allow to search for users to invite.
 */
abstract class InviteFormBase extends FormBase {

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
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The invitation message helper service.
   *
   * @var \Drupal\joinup_invite\InvitationMessageHelperInterface
   */
  protected $invitationMessageHelper;

  /**
   * The entity related to the invitation.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected $entity;

  /**
   * Constructs a new InviteFormBase object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\joinup_invite\InvitationMessageHelperInterface $invitation_message_helper
   *   The invitation message helper service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, InvitationMessageHelperInterface $invitation_message_helper) {
    $this->entityTypeManager = $entityTypeManager;
    $this->invitationMessageHelper = $invitation_message_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('joinup_invite.invitation_message_helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $form, FormStateInterface $form_state) {
    // Initialise the user list if needed.
    if (!$form_state->has('user_list')) {
      $form_state->set('user_list', []);
    }

    $form['#id'] = Html::getId($this->getFormId());
    $form['#attributes']['class'][] = 'invite-form';

    $form['autocomplete'] = [
      '#type' => 'textfield',
      '#title' => $this->t('E-mail'),
      '#description' => $this->t('Enter a Joinup user e-mail address or start typing to search for a user and select it with the mouse or the keyboard.'),
      '#autocomplete_route_name' => 'joinup_invite.user_auto_complete',
      '#attributes' => [
        'class' => ['invite-autocomplete'],
      ],
      '#attached' => [
        'library' => [
          'joinup_invite/invite_autocomplete',
        ],
      ],
      '#weight' => -100,
    ];

    $form['add'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add'),
      '#name' => 'add_user',
      '#validate' => ['::validateAddUser'],
      '#submit' => ['::submitAddUser'],
      '#ajax' => [
        // Replace the whole form when adding a user.
        'callback' => '::ajaxUpdateForm',
        'wrapper' => $form['#id'],
      ],
      // Disable refocus of element after ajax callbacks.
      '#attributes' => [
        'class' => ['invite-form__add'],
        'data-disable-refocus' => 1,
      ],
      '#weight' => -99,
    ];

    $form['users'] = [
      '#type' => 'container',
      '#tree' => TRUE,
      '#weight' => -98,
      '#attributes' => [
        'id' => 'users-list',
        'class' => ['invite-form__list'],
      ],
    ];
    $user_list = $form_state->get('user_list');
    foreach ($user_list as $delta => $mail) {
      $form['users'][$delta] = [
        '#type' => 'chip',
        '#text' => $this->getAccountName($this->loadUserByMail($mail)),
        // Store the mail that this button will remove, to simplify the
        // operation.
        '#mail' => $mail,
        // No need to run validations when removing a line.
        '#limit_validation_errors' => [],
        '#submit' => ['::submitRemoveUser'],
        '#ajax' => [
          'callback' => '::ajaxUpdateUserList',
          'wrapper' => 'users-list',
        ],
      ];
    }

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#attributes' => ['class' => ['button']],
      '#url' => $this->getCancelButtonUrl(),
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->getSubmitButtonText(),
    ];

    return $form;
  }

  /**
   * Validates the action of adding a new user to the list.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateAddUser(array &$form, FormStateInterface $form_state) {
    $mail = trim($form_state->getValue('autocomplete'));
    if (empty($mail)) {
      $form_state->setError($form['autocomplete'], $this->t('No mail provided.'));
    }

    $user = $this->loadUserByMail($mail);
    if (empty($user)) {
      $form_state->setError($form['autocomplete'], $this->t('No user found with mail %mail.', ['%mail' => $mail]));
    }

    if (in_array($mail, $form_state->get('user_list'))) {
      $form_state->setError($form['autocomplete'], $this->t('The user with mail %mail has been already added to the list.', ['%mail' => $mail]));
    }
  }

  /**
   * Submit callback that adds a new user to the list.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitAddUser(array $form, FormStateInterface $form_state) {
    $list = $form_state->get('user_list');
    $list[] = trim($form_state->getValue('autocomplete'));
    $form_state->set('user_list', $list);

    // Clear the autocomplete field so it's ready for a new input.
    $form_state->setValueForElement($form['autocomplete'], '');
    NestedArray::setValue($form_state->getUserInput(), $form['autocomplete']['#parents'], '');

    $form_state->setRebuild();
  }

  /**
   * Submit callback to remove a user from the list.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitRemoveUser(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    $list = $form_state->get('user_list');
    $list = array_diff($list, [$element['#mail']]);
    $form_state->set('user_list', $list);

    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (empty($form_state->get('user_list'))) {
      $form_state->setError($form, $this->t('Please add at least one user.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $users = $this->getUserList($form_state);
    $results = array_fill_keys(array_keys(static::INVITATION_MESSAGE_TYPES), 0);

    foreach ($users as $user) {
      try {
        $result_status = $this->processUser($user);
        $results[$result_status]++;
      }
      catch (\Exception $e) {
        // An error occurred. This can be because an invitation has an incorrect
        // status or because a storage error occurred during the creation of the
        // invitation entity. This is unexpected but is not due to a mistake by
        // the end user. Let's log an error and continue sending the rest of the
        // messages.
        $this->logger('joinup_invite')->error($e->getMessage());
      }
    }

    $this->processResults($results);
  }

  /**
   * Processes a single user and creates the invitation when available.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user to process.
   *
   * @return string
   *   One of the statuses of the invitation.
   *
   * @throws \Exception
   *   Thrown when an invitation status is invalid.
   */
  protected function processUser(UserInterface $user): string {
    // Check if a previous invitation already exists.
    $invitation = Invitation::loadByEntityAndUser($this->entity, $user, $this->getInvitationType());
    if (!empty($invitation)) {
      switch ($invitation->getStatus()) {
        // If the invitation was already accepted, don't send an invitation.
        case InvitationInterface::STATUS_ACCEPTED:
          $status = static::RESULT_ACCEPTED;
          break;

        // If the invitation was already rejected, don't send an invitation.
        case InvitationInterface::STATUS_REJECTED:
          $status = static::RESULT_REJECTED;
          break;

        // If the invitation is still pending, resend the invitation.
        case InvitationInterface::STATUS_PENDING:
          $success = $this->sendMessage($invitation);
          $status = $success ? static::RESULT_RESENT : static::RESULT_FAILED;
          break;

        default:
          throw new \Exception('Unknown invitation status: "' . $invitation->getStatus() . '".');
      }
      return $status;
    }

    // No previous invitation exists. Create it.
    $invitation = $this->createInvitation($user);

    // Send the notification message for the invitation.
    try {
      $success = $this->sendMessage($invitation);
      $status = $success ? static::RESULT_SUCCESS : static::RESULT_FAILED;
    }
    catch (\Exception $e) {
      $status = static::RESULT_FAILED;
    }

    return $status;
  }

  /**
   * Creates and returns an invitation entity.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user associated with the invitation.
   *
   * @return \Drupal\joinup_invite\Entity\InvitationInterface
   *   The invitation entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown in case the Invitation entity has a bad definition.
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown when the Invitation entity is not defined.
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown when an error occurs during the saving of the invitation.
   * @throws \Exception
   *   Thrown when the user already has an invitation.
   */
  protected function createInvitation(UserInterface $user): InvitationInterface {
    /** @var \Drupal\joinup_invite\Entity\InvitationInterface $invitation */
    $invitation = $this->entityTypeManager->getStorage('invitation')->create([
      'bundle' => $this->getInvitationType(),
    ]);
    $invitation
      ->setRecipient($user)
      ->setEntity($this->entity)
      ->save();

    return $invitation;
  }

  /**
   * Sends a new message to invite the given user to the given entity.
   *
   * @param \Drupal\joinup_invite\Entity\InvitationInterface $invitation
   *   The invitation.
   *
   * @return bool
   *   Whether or not the message was successfully delivered.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   *   Thrown when the URL for the entity cannot be generated.
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown when the message cannot be saved to the database.
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   *   Thrown when the first name or last name of the current user is not known.
   */
  protected function sendMessage(InvitationInterface $invitation): bool {
    $arguments = $this->generateArguments($invitation->getEntity());
    $message = $this->invitationMessageHelper->createMessage($invitation, $this->getTemplateId(), $arguments);
    $message->save();

    return $this->invitationMessageHelper->sendMessage($invitation, $this->getTemplateId());
  }

  /**
   * Returns the message template ID of the message to be sent for the invite.
   *
   * @return string
   *   The template ID.
   */
  abstract protected function getTemplateId(): string;

  /**
   * Processes the results array. Suitable to display messages etc.
   *
   * @param array $results
   *   An array of invitation statuses and the amount of invitations processed
   *   of each status.
   */
  protected function processResults(array $results): void {}

  /**
   * Ajax callback that returns the updated users list.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   A render array.
   */
  public function ajaxUpdateUserList(array $form, FormStateInterface $form_state) {
    return $form['users'];
  }

  /**
   * Ajax callback that returns the updated form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   A render array.
   */
  public function ajaxUpdateForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * Returns all the users specified in the form state as fully loaded entities.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\user\UserInterface[]
   *   An array of loaded user entities.
   */
  protected function getUserList(FormStateInterface $form_state): array {
    $list = [];
    foreach ($form_state->get('user_list') as $mail) {
      $list[] = $this->loadUserByMail($mail);
    }

    return $list;
  }

  /**
   * Loads a user by its mail.
   *
   * @param string $mail
   *   The mail of the user.
   *
   * @return \Drupal\user\UserInterface|null
   *   A loaded user object. Null if the mail matches no users in the system.
   */
  protected function loadUserByMail($mail): ?UserInterface {
    $users = $this->entityTypeManager->getStorage('user')
      ->loadByProperties(['mail' => $mail]);

    return empty($users) ? NULL : reset($users);
  }

  /**
   * Returns a full name of the user with their email as suffix in parenthesis.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user object.
   *
   * @return string
   *   A string version of user's full name.
   */
  protected function getAccountName(UserInterface $user): string {
    return $user->getDisplayName();
  }

  /**
   * Returns the arguments for an invitation message.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to generate the message arguments.
   *
   * @return array
   *   The message arguments.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   *   Thrown when the first name or last name of the current user is not known.
   * @throws \Drupal\Core\Entity\EntityMalformedException
   *   Thrown when the URL for the entity cannot be generated.
   */
  protected function generateArguments(EntityInterface $entity): array {
    $arguments = [];

    $arguments['@entity:title'] = $entity->label();
    $arguments['@entity:bundle'] = $entity->bundle();
    $arguments['@entity:url'] = $entity->toUrl('canonical', [
      'absolute' => TRUE,
    ])->toString();

    $arguments += MessageArgumentGenerator::getActorArguments();

    return $arguments;
  }

  /**
   * Returns the text for the form submit button.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The button text.
   */
  abstract protected function getSubmitButtonText(): TranslatableMarkup;

  /**
   * Returns the route to go to if the user clicks the cancel button.
   *
   * @return \Drupal\Core\Url
   *   A URL object.
   */
  abstract protected function getCancelButtonUrl(): Url;

  /**
   * Returns the invitation bundle.
   *
   * @return string
   *   The invitation bundle.
   */
  abstract protected function getInvitationType(): string;

}
