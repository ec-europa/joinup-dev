<?php

namespace Drupal\moderation\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\rdf_entity\RdfInterface;

/**
 * Form that allows to display and filter the content moderation overview.
 */
class ContentModerationOverviewForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'content_moderation_overview_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, RdfInterface $rdf_entity = NULL) {
    $form = [
      'test' => [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('Hello, world!'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $todo = TRUE;
  }

  /**
   * Access check for the content moderation overview.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The collection that is on the verge of losing a member.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result object.
   */
  public static function access(RdfInterface $rdf_entity) {
    /** @var \Drupal\Core\Session\AccountProxyInterface $account_proxy */
    $account_proxy = \Drupal::service('current_user');
    $user = $account_proxy->getAccount();

    /** @var \Drupal\og\MembershipManagerInterface $membership_manager */
    $membership_manager = \Drupal::service('og.membership_manager');

    $access = FALSE;

    // Only allow access if the current user is a moderator or a collection
    // facilitator.
    if (in_array('moderator', $user->getRoles())) {
      $access = TRUE;
    }
    elseif ($membership_manager->isMember($rdf_entity, $user)) {
      $membership = $membership_manager->getMembership($rdf_entity, $user);
      if (in_array('rdf_entity-collection-facilitator', $membership->getRolesIds())) {
        $access = TRUE;
      }
    }

    return AccessResult::allowedIf($access);
  }

}
