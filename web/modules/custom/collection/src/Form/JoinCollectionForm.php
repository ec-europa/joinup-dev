<?php

declare(strict_types = 1);

namespace Drupal\collection\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\og\Entity\OgMembership;
use Drupal\og\MembershipManagerInterface;
use Drupal\og\OgMembershipInterface;
use Drupal\og\OgRoleInterface;
use Drupal\rdf_entity\RdfInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A simple form with a button to join or leave a collection.
 */
class JoinCollectionForm extends FormBase {
  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The membership manager service.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $membershipManager;

  /**
   * The access manager service.
   *
   * @var \Drupal\Core\Access\AccessManagerInterface
   */
  protected $accessManager;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Constructs a JoinCollectionForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\og\MembershipManagerInterface $membership_manager
   *   The membership manager service.
   * @param \Drupal\Core\Access\AccessManagerInterface $access_manager
   *   The access manager service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MembershipManagerInterface $membership_manager, AccessManagerInterface $access_manager, MessengerInterface $messenger, FormBuilderInterface $form_builder) {
    $this->entityTypeManager = $entity_type_manager;
    $this->membershipManager = $membership_manager;
    $this->accessManager = $access_manager;
    $this->messenger = $messenger;
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('og.membership_manager'),
      $container->get('access_manager'),
      $container->get('messenger'),
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'join_collection_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?AccountInterface $user = NULL, ?RdfInterface $collection = NULL): array {
    $form['#access'] = $this->access();

    $user = $this->loadUser((int) $user->id());
    $form['collection_id'] = [
      '#type' => 'value',
      '#value' => $collection->id(),
    ];
    $form['user_id'] = [
      '#type' => 'value',
      '#value' => $user->id(),
    ];

    // In case the user is not a member or does not have a pending membership,
    // give the possibility to request one.
    $membership = $this->getUserNonBlockedMembership($user, $collection);
    if (empty($membership)) {
      $form['join'] = [
        '#ajax' => [
          'callback' => '::showSubscribeDialog',
        ],
        '#type' => 'submit',
        '#value' => $this->t('Join this collection'),
      ];
    }

    // If the user is already a member of the collection, show a link to the
    // membership cancellation confirmation form, disguised as a form submit
    // button. The confirmation form should open in a modal dialog for
    // JavaScript-enabled browsers.
    elseif ($membership->getState() === OgMembershipInterface::STATE_ACTIVE) {
      $parameters = ['rdf_entity' => $collection->id()];
      $form['leave'] = [
        '#theme' => 'collection_leave_button',
      ];
      if ($this->accessManager->checkNamedRoute('collection.leave_confirm_form', $parameters)) {
        $form['leave']['#confirm'] = [
          '#type' => 'link',
          '#title' => $this->t('Leave this collection'),
          '#url' => Url::fromRoute('collection.leave_confirm_form', $parameters),
          '#attributes' => [
            'class' => ['use-ajax'],
            'data-dialog-type' => 'modal',
            'data-dialog-options' => Json::encode(['width' => 'auto']),
          ],
        ];
      }
      else {
        $form['leave']['#confirm'] = [
          '#markup' => $this->t('You cannot leave the %collection collection', ['%collection' => $collection->label()]),
        ];
      }
      $form['#attached']['library'][] = 'core/drupal.ajax';
    }
    // If the user has a pending membership, do not allow to request a new one.
    elseif ($membership->getState() === OgMembershipInterface::STATE_PENDING) {
      $form['pending'] = [
        '#type' => 'link',
        '#title' => $this->t('Membership is pending'),
        '#url' => Url::fromRoute('<current>', [], ['fragment' => 'pending']),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    $collection = $this->loadCollection($form_state->getValue('collection_id'));
    $user = $this->loadUser((int) $form_state->getValue('user_id'));

    // Only authenticated users can join a collection.
    if ($user->isAnonymous()) {
      $form_state->setErrorByName('user', $this->t('<a href=":login">Sign in</a> or <a href=":register">register</a> to change your group membership.', [
        ':login' => $this->urlGenerator->generateFromRoute('user.login'),
        ':register' => $this->urlGenerator->generateFromRoute('user.register'),
      ]));
    }

    $membership = $this->getUserNonBlockedMembership($user, $collection);
    // Make sure the user is not already a member.
    if (!empty($membership)) {
      $form_state->setErrorByName('collection', $this->t('You already are a member of this collection.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $collection = $this->loadCollection($form_state->getValue('collection_id'));
    $user = $this->loadUser((int) $form_state->getValue('user_id'));
    $role_id = $collection->getEntityTypeId() . '-' . $collection->bundle() . '-' . OgRoleInterface::AUTHENTICATED;
    $og_roles = [$this->loadOgRole($role_id)];
    if ($collection->field_ar_new_member_role->value === 'rdf_entity-collection-author') {
      $og_roles[] = $this->loadOgRole('rdf_entity-collection-author');
    }
    $state = $collection->get('field_ar_closed')->first()->value ? OgMembershipInterface::STATE_PENDING : OgMembershipInterface::STATE_ACTIVE;

    /** @var \Drupal\og\OgMembershipInterface $membership */
    $membership = $this->entityTypeManager->getStorage('og_membership')->create([
      'type' => OgMembershipInterface::TYPE_DEFAULT,
    ]);
    $membership
      ->setOwner($user)
      ->setGroup($collection)
      ->setState($state)
      ->setRoles($og_roles)
      ->save();

    $parameters = ['%collection' => $collection->getName()];
    $message = $state === OgMembership::STATE_ACTIVE ?
      $this->t('You are now a member of %collection.', $parameters) :
      $this->t('Your membership to the %collection collection is under approval.', $parameters);
    $this->messenger->addStatus($message);
  }

  /**
   * AJAX callback showing a form to subscribe to the collection after joining.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response.
   */
  public function showSubscribeDialog(array &$form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();

    // Output messages in the page.
    $messages = ['#type' => 'status_messages'];
    $response->addCommand(new PrependCommand('.section--content-top', $messages));

    // If the form submitted successfully, make an offer the user cannot refuse.
    if (!$form_state->getErrors()) {
      $collection = $this->loadCollection($form_state->getValue('collection_id'));

      // Rebuild the form and replace it in the page, so that the "Join this
      // "collection" button will be replaced with either the "You're a member"
      // button or the "Membership is pending" button.
      $form_button = $this->formBuilder->rebuildForm('join-collection-form', $form_state, $form);
      $response->addCommand(new ReplaceCommand('#join-collection-form', $form_button));

      $title = $this->t('Welcome to %collection', ['%collection' => $collection->label()]);

      $modal_form = $this->formBuilder->getForm('\Drupal\collection\Form\SubscribeToCollectionForm', $collection);
      $modal_form['#attached']['library'][] = 'core/drupal.dialog.ajax';

      $response->addCommand(new OpenModalDialogCommand($title, $modal_form, ['width' => '500']));
    }
    return $response;
  }

  /**
   * Access check for the form.
   *
   * @return bool
   *   True if the form can be accessed, false otherwise.
   */
  public function access(): bool {
    return $this->currentUser()->isAuthenticated() && $this->getRouteMatch()->getRouteName() !== 'collection.leave_confirm_form';
  }

  /**
   * Returns a membership of the user that is active or pending.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user entity.
   * @param \Drupal\rdf_entity\RdfInterface $collection
   *   The group entity.
   *
   * @return \Drupal\og\OgMembershipInterface|null
   *   The membership of the user or null.
   */
  protected function getUserNonBlockedMembership(UserInterface $user, RdfInterface $collection): ?OgMembershipInterface {
    $membership = $this->membershipManager->getMembership($collection, $user->id(), [
      OgMembershipInterface::STATE_ACTIVE,
      OgMembershipInterface::STATE_PENDING,
    ]);
    return $membership;
  }

  /**
   * Loads the collection with the given ID.
   *
   * @param string $collection_id
   *   The collection ID.
   *
   * @return \Drupal\rdf_entity\RdfInterface
   *   The collection.
   */
  protected function loadCollection(string $collection_id): RdfInterface {
    return $this->entityTypeManager->getStorage('rdf_entity')->load($collection_id);
  }

  /**
   * Loads the user with the given ID.
   *
   * @param int $user_id
   *   The user ID.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity.
   */
  protected function loadUser(int $user_id): UserInterface {
    return $this->entityTypeManager->getStorage('user')->load($user_id);
  }

  /**
   * Loads the OG role with the given ID.
   *
   * @param string $role_id
   *   The OG role ID.
   *
   * @return \Drupal\og\OgRoleInterface
   *   The OG role entity.
   */
  protected function loadOgRole(string $role_id): OgRoleInterface {
    return $this->entityTypeManager->getStorage('og_role')->load($role_id);
  }

}
