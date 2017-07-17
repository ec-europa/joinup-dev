<?php

namespace Drupal\collection\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\og\Entity\OgMembership;
use Drupal\og\Entity\OgRole;
use Drupal\og\MembershipManagerInterface;
use Drupal\og\OgMembershipInterface;
use Drupal\og\OgRoleInterface;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\rdf_entity\RdfInterface;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A simple form with a button to join or leave a collection.
 */
class JoinCollectionForm extends FormBase {

  /**
   * The membership manager service.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $membershipManager;

  /**
   * Constructs a JoinCollectionForm.
   *
   * @param \Drupal\og\MembershipManagerInterface $membership_manager
   *   The membership manager service.
   */
  public function __construct(MembershipManagerInterface $membership_manager) {
    $this->membershipManager = $membership_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
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

    $user = User::load($user->id());
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
    $membership = $this->membershipManager->getMembership($collection, $user, [
      OgMembershipInterface::STATE_ACTIVE,
      OgMembershipInterface::STATE_PENDING,
    ]);
    $button_classes = [
      'button',
      'button--blue-light',
      'mdl-button',
      'mdl-js-button',
      'mdl-button--raised',
      'mdl-js-ripple-effect',
      'mdl-button--accent',
    ];

    if (empty($membership)) {
      $form['join'] = [
        '#attributes' => [
          'class' => $button_classes,
        ],
        '#type' => 'submit',
        '#value' => $this->t('Join this collection'),
      ];
    }
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

    $collection = Rdf::load($form_state->getValue('collection_id'));

    // Only authenticated users can join a collection.
    /** @var \Drupal\user\UserInterface $user */
    $user = User::load($form_state->getValue('user_id'));
    if ($user->isAnonymous()) {
      $form_state->setErrorByName('user', $this->t('<a href=":login">Log in</a> or <a href=":register">register</a> to change your group membership.', [
        ':login' => Url::fromRoute('user.login'),
        ':register' => Url::fromRoute('user.register'),
      ]));
    }

    $membership = $this->membershipManager->getMembership($collection, $user, [
      OgMembershipInterface::STATE_PENDING,
      OgMembershipInterface::STATE_ACTIVE,
    ]);

    if (!empty($membership)) {
      $form_state->setErrorByName('collection', $this->t('You already are a member of this collection.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\rdf_entity\Entity\RdfInterface $collection */
    $collection = Rdf::load($form_state->getValue('collection_id'));
    /** @var \Drupal\user\UserInterface $user */
    $user = User::load($form_state->getValue('user_id'));
    $role_id = $collection->getEntityTypeId() . '-' . $collection->bundle() . '-' . OgRoleInterface::AUTHENTICATED;
    $state = $collection->get('field_ar_closed')->first()->value ? OgMembershipInterface::STATE_PENDING : OgMembershipInterface::STATE_ACTIVE;
    $membership = OgMembership::create();
    $membership
      ->setUser($user)
      ->setGroup($collection)
      ->setState($state)
      ->setRoles([OgRole::load($role_id)])
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

}
