<?php

namespace Drupal\joinup_subscription\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SubscriptionConfigurationForm.
 *
 * @package Drupal\joinup_subscription\Form
 */
class SubscriptionConfigurationForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $subscription_configuration = $this->entity;
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $subscription_configuration->label(),
      '#description' => $this->t("Label for the Subscription configuration."),
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $subscription_configuration->id(),
      '#machine_name' => array(
        'exists' => '\Drupal\joinup_subscription\Entity\SubscriptionConfiguration::load',
      ),
      '#disabled' => !$subscription_configuration->isNew(),
    );

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $subscription_configuration = $this->entity;
    $status = $subscription_configuration->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Subscription configuration.', [
          '%label' => $subscription_configuration->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Subscription configuration.', [
          '%label' => $subscription_configuration->label(),
        ]));
    }
    $form_state->setRedirectUrl($subscription_configuration->urlInfo('collection'));
  }

}
