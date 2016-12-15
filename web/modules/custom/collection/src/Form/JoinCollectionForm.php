<?php

namespace Drupal\collection\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\og\Entity\OgMembership;
use Drupal\og\Entity\OgRole;
use Drupal\og\Og;
use Drupal\og\OgMembershipInterface;
use Drupal\og\OgRoleInterface;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\rdf_entity\RdfInterface;
use Drupal\user\Entity\User;

/**
 * A simple form with a button to join or leave a collection.
 *
 * @package Drupal\collection\Form
 */
class JoinCollectionForm extends FormBase {

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
    if (Og::isMember($collection, $user)) {
      $form['leave'] = [
        '#type' => 'link',
        '#title' => $this->t('Leave this collection'),
        '#url' => Url::fromRoute('collection.leave_confirm_form', [
          'rdf_entity' => $collection->id(),
        ]),
        '#attributes' => [
          'class' => [
            'use-ajax',
            'button',
            'button--small',
            'button--blue-light',
            'mdl-button',
            'mdl-js-button',
            'mdl-button--raised',
            'mdl-js-ripple-effect',
            'mdl-button--accent',
          ],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode(['width' => 'auto']),
        ],
      ];
      $form['#attached']['library'][] = 'core/drupal.ajax';
    }

    // If the user is not yet a member, show the join button.
    else {
      $form['join'] = [
        '#attributes' => [
          'class' => [
            'button',
            'button--blue-light',
            'mdl-button',
            'mdl-js-button',
            'mdl-button--raised',
            'mdl-js-ripple-effect',
            'mdl-button--accent',
          ],
        ],
        '#type' => 'submit',
        '#value' => $this->t('Join this collection'),
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

    // Check if the user is already a member of the collection.
    if (Og::isMember($collection, $user)) {
      $form_state->setErrorByName('collection', $this->t('You already are a member of this collection.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var RdfInterface $collection */
    $collection = Rdf::load($form_state->getValue('collection_id'));
    /** @var \Drupal\user\UserInterface $user */
    $user = User::load($form_state->getValue('user_id'));
    $role_id = $collection->getEntityTypeId() . '-' . $collection->bundle() . '-' . OgRoleInterface::AUTHENTICATED;

    $membership = OgMembership::create();
    $membership
      ->setUser($user)
      ->setGroup($collection)
      ->setState(OgMembershipInterface::STATE_ACTIVE)
      ->setRoles([OgRole::load($role_id)])
      ->save();

    drupal_set_message($this->t('You are now a member of %collection.', [
      '%collection' => $collection->getName(),
    ]));
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
