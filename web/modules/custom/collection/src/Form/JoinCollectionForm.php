<?php

declare(strict_types = 1);

namespace Drupal\collection\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\joinup_group\Form\JoinGroupFormBase;
use Drupal\og\OgMembershipInterface;
use Drupal\rdf_entity\RdfInterface;

/**
 * A simple form with a button to join or leave a collection.
 */
class JoinCollectionForm extends JoinGroupFormBase {

  /**
   * The state of the new membership the user will get.
   *
   * @var string
   */
  protected $membershipState;

  /**
   * {@inheritdoc}
   */
  public function getSuccessMessage(OgMembershipInterface $membership): TranslatableMarkup {
    $parameters = [
      '%group' => $this->group->getName(),
      ':bundle' => $this->group->bundle(),
    ];
    return $membership->getState() === OgMembershipInterface::STATE_ACTIVE ?
      $this->t('You are now a member of %group.', $parameters) :
      $this->t('Your membership to the %group :bundle is under approval.', $parameters);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?AccountProxyInterface $user = NULL, ?RdfInterface $group = NULL): array {
    $form = parent::buildForm($form, $form_state, $user, $group);

    // @todo Move this into the joinup_subscription module to solve a circular
    //   dependency.
    // @see https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-6330
    $membership = $this->getUserNonBlockedMembership($user, $group);
    if (empty($membership)) {
      // Show the subscription dialog in a modal on join.
      $form['join']['#ajax'] = ['callback' => '::showSubscribeDialog'];
    }

    return $form;
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
      // Rebuild the form and replace it in the page, so that the "Join this
      // "collection" button will be replaced with either the "You're a member"
      // button or the "Membership is pending" button.
      $form_button = $this->formBuilder->rebuildForm('join-group-form', $form_state, $form);
      $response->addCommand(new ReplaceCommand('#join-group-form', $form_button));

      $title = $this->t('Welcome to %collection', ['%collection' => $this->group->label()]);

      $modal_form = $this->formBuilder->getForm(SubscribeToCollectionForm::class, $this->group);
      $modal_form['#attached']['library'][] = 'core/drupal.dialog.ajax';

      $response->addCommand(new OpenModalDialogCommand($title, $modal_form, ['width' => '500']));
    }
    return $response;
  }

}
