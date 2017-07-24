<?php

namespace Drupal\collection\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
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
   * Constructs a JoinCollectionForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\og\MembershipManagerInterface $membership_manager
   *   The membership manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MembershipManagerInterface $membership_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->membershipManager = $membership_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('og.membership_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'join_collection_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, AccountProxyInterface $user = NULL, RdfInterface $collection = NULL) {
    $form['#access'] = $this->access();

    $user = $this->entityTypeManager->getStorage('user')->load($user->id());
    $form['collection_id'] = [
      '#type' => 'hidden',
      '#title' => $this->t('Collection ID'),
      '#value' => $collection->id(),
    ];
    $form['user_id'] = [
      '#type' => 'hidden',
      '#title' => $this->t('User ID'),
      '#value' => $user->id(),
    ];

    // If the user is already a member of the collection, show a link to the
    // confirmation form, disguised as a form submit button. The confirmation
    // form should open in a modal dialog for JavaScript-enabled browsers.
    $membership = $this->getUserNonBlockedMembership($user, $collection);
    $button_classes = [
      'button',
      'button--blue-light',
      'mdl-button',
      'mdl-js-button',
      'mdl-button--raised',
      'mdl-js-ripple-effect',
      'mdl-button--accent',
    ];

    // In case the user is not a member or does not have a pending membership,
    // give the possibility to request one.
    if (empty($membership)) {
      $form['join'] = [
        '#attributes' => [
          'class' => $button_classes,
        ],
        '#type' => 'submit',
        '#value' => $this->t('Join this collection'),
      ];
    }
    // If the user has an active membership, he can cancel it as well.
    elseif ($membership->getState() === OgMembershipInterface::STATE_ACTIVE) {
      $form['leave'] = [
        '#type' => 'link',
        '#title' => $this->t('Leave this collection'),
        '#url' => Url::fromRoute('collection.leave_confirm_form', [
          'rdf_entity' => $collection->id(),
        ]),
        '#attributes' => [
          'class' => array_merge($button_classes, [
            'use-ajax',
            'button--small',
          ]),
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode(['width' => 'auto']),
        ],
      ];
      $form['#attached']['library'][] = 'core/drupal.ajax';
    }
    // If the user has a pending membership, do not allow to request a new one.
    elseif ($membership->getState() === OgMembershipInterface::STATE_PENDING) {
      $form['pending'] = [
        '#type' => 'link',
        '#title' => $this->t('Membership is pending'),
        '#url' => Url::fromRoute('<current>', [], ['fragment' => 'pending']),
        '#attributes' => [
          'class' => array_merge($button_classes, ['button--small']),
        ],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $collection_id = $form_state->getValue('collection_id');
    $collection = $this->entityTypeManager->getStorage('rdf_entity')->load($collection_id);

    // Only authenticated users can join a collection.
    $user_id = $form_state->getValue('user_id');
    /** @var \Drupal\user\UserInterface $user */
    $user = $this->entityTypeManager->getStorage('user')->load($user_id);
    if ($user->isAnonymous()) {
      $form_state->setErrorByName('user', $this->t('<a href=":login">Log in</a> or <a href=":register">register</a> to change your group membership.', [
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
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $collection_id = $form_state->getValue('collection_id');
    $collection = $this->entityTypeManager->getStorage('rdf_entity')->load($collection_id);
    $user_id = $form_state->getValue('user_id');
    /** @var \Drupal\user\UserInterface $user */
    $user = $this->entityTypeManager->getStorage('user')->load($user_id);
    $role_id = $collection->getEntityTypeId() . '-' . $collection->bundle() . '-' . OgRoleInterface::AUTHENTICATED;
    $og_role = $this->entityTypeManager->getStorage('og_role')->load($role_id);
    $state = $collection->get('field_ar_closed')->first()->value ? OgMembershipInterface::STATE_PENDING : OgMembershipInterface::STATE_ACTIVE;
    $membership = $this->entityTypeManager->getStorage('og_membership')->create([
      'type' => OgMembershipInterface::TYPE_DEFAULT,
    ]);
    $membership
      ->setUser($user)
      ->setGroup($collection)
      ->setState($state)
      ->setRoles([$og_role])
      ->save();

    $parameters = ['%collection' => $collection->getName()];
    $message = ($state === OgMembership::STATE_ACTIVE) ?
      $this->t('You are now a member of %collection.', $parameters) :
      $this->t('Your membership to the %collection collection is under approval.', $parameters);
    drupal_set_message($message);
  }

  /**
   * Access check for the form.
   *
   * @return bool
   *   True if the form can be access, false otherwise.
   */
  public function access() {
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
  protected function getUserNonBlockedMembership(UserInterface $user, RdfInterface $collection) {
    $membership = $this->membershipManager->getMembership($collection, $user, [
      OgMembershipInterface::STATE_ACTIVE,
      OgMembershipInterface::STATE_PENDING,
    ]);
    return $membership;
  }

}
