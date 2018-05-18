<?php

declare(strict_types = 1);

namespace Drupal\joinup_invite\Form;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for forms that allow to search for users to invite.
 */
abstract class InviteFormBase extends FormBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new InviteFormBase object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
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
   * Helper method to load all the users specified in the form state.
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
    $user = $this->entityTypeManager->getStorage('user')
      ->loadByProperties(['mail' => $mail]);

    return empty($user) ? NULL : reset($user);
  }

  /**
   * Returns a full name of the user with his email as suffix in parenthesis.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user object.
   *
   * @return string
   *   A string version of user's full name.
   */
  protected function getAccountName(UserInterface $user): string {
    return $user->get('full_name')->value;
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

}
