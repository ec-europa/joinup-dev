<?php

/**
 * @file
 * Contains \Drupal\collection\Form\JoinCollectionForm.
 */

namespace Drupal\collection\Form;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\collection\CollectionInterface;
use Drupal\collection\Entity\Collection;
use Drupal\og\Og;
use Drupal\og\OgGroupAudienceHelper;
use Drupal\og\OgMembershipInterface;
use Drupal\user\Entity\User;

/**
 * A simple form with a button to join a collection.
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
  public function buildForm(array $form, FormStateInterface $form_state, AccountProxyInterface $user = NULL, CollectionInterface $collection = NULL) {
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
    $form['join'] = [
      '#type' => 'submit',
      '#op' => 'join',
      '#value' => $this->t('Join this collection'),
      '#access' => !Og::isMember($collection, $user),
    ];
    $form['leave'] = [
      '#type' => 'submit',
      '#op' => 'leave',
      '#value' => $this->t('Leave this collection'),
      '#access' => Og::isMember($collection, $user),
    ];

    // This form varies by user and collection.
    $metadata = new CacheableMetadata();
    $metadata
      ->merge(CacheableMetadata::createFromObject($user))
      ->merge(CacheableMetadata::createFromObject($collection))
      ->applyTo($form);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $operation = $form_state->getTriggeringElement()['#op'];
    $collection = Collection::load($form_state->getValue('collection_id'));

    // Only authenticated users can join a collection.
    /** @var \Drupal\user\UserInterface $user */
    $user = User::load($form_state->getValue('user_id'));
    if ($user->isAnonymous()) {
      $form_state->setErrorByName('user', $this->t('<a href=":login">Log in</a> or <a href=":register">register</a> to change your group membership.', [
        ':login' => Url::fromRoute('user.login'),
        ':register' => Url::fromRoute('user.register'),
      ]));
    }

    // Check if the chosen operation is valid for the user.
    if (Og::isMember($collection, $user) && $operation === 'join') {
      $form_state->setErrorByName('collection', $this->t('You already are a member of this collection.'));
    }
    elseif (!Og::isMember($collection, $user) && $operation === 'leave') {
      $form_state->setErrorByName('collection', $this->t('You already have left this collection.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var CollectionInterface $collection */
    $collection = Collection::load($form_state->getValue('collection_id'));
    /** @var \Drupal\user\UserInterface $user */
    $user = User::load($form_state->getValue('user_id'));

    switch ($form_state->getTriggeringElement()['#op']) {
      case 'join':
        $membership = Og::membershipStorage()->create(Og::membershipDefault());
        $membership
          ->setFieldName(OgGroupAudienceHelper::DEFAULT_FIELD)
          ->setMemberEntityType('user')
          ->setMemberEntityId($user->id())
          ->setGroupEntityType('collection')
          ->setGroupEntityid($collection->id())
          ->setState(OgMembershipInterface::STATE_ACTIVE)
          ->save();

        drupal_set_message($this->t('You are now a member of %collection.', [
          '%collection' => $collection->getName(),
        ]));
        break;

      case 'leave':
        $membership_ids = \Drupal::entityQuery('og_membership')
          ->condition('member_entity_id', $user->id())
          ->condition('member_entity_type', 'user')
          ->condition('group_entity_id', $collection->id())
          ->condition('group_entity_type', 'collection')
          ->execute();
        $memberships = Og::membershipStorage()->loadMultiple($membership_ids);
        Og::membershipStorage()->delete($memberships);

        drupal_set_message($this->t('You are no longer a member of %collection.', [
          '%collection' => $collection->getName(),
        ]));
        break;
    }
  }

}
