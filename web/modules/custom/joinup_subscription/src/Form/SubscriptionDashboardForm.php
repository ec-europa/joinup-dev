<?php

namespace Drupal\joinup_subscription\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\joinup_core\JoinupRelationManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Joinup subscription form.
 */
class SubscriptionDashboardForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Joinup relation manager service.
   *
   * @var \Drupal\joinup_core\JoinupRelationManagerInterface
   */
  protected $relationManager;

  /**
   * Constructs a new SubscriptionDashboardForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\joinup_core\JoinupRelationManagerInterface $relationManager
   *   The Joinup relation manager service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, JoinupRelationManagerInterface $relationManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->relationManager = $relationManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('joinup_core.relations_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'joinup_subscription_dashboard';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, AccountInterface $user = NULL) {
    // When no user is passed we cannot show anything useful.
    if (empty($user)) {
      throw new \InvalidArgumentException('No user account supplied.');
    }

    $memberships = $this->relationManager->getUserGroupMembershipsByBundle($user, 'rdf_entity', 'collection');

    $form['description'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Set your preferences to receive notifications on a per collection basis.'),
    ];

    $empty_message = $this->t('No collection memberships yet. Join one or more collections to subscribe to their content!');
    $form['empty_text'] = [
      '#theme' => 'status_messages',
      '#message_list' => ['status' => [$empty_message]],
      '#status_headings' => [
        'status' => t('Status message'),
        'error' => t('Error message'),
        'warning' => t('Warning message'),
      ],
      '#access' => !(bool) count($memberships),
    ];

    foreach ($memberships as $membership) {
      $collection = $membership->getGroup();
      if ($collection === NULL) {
        continue;
      }
      $form['collections'][$collection->id()]['preview'] = $this->entityTypeManager->getViewBuilder($collection->getEntityTypeId())->view($collection, 'view_mode_featured');
    }

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (mb_strlen($form_state->getValue('message')) < 10) {
      $form_state->setErrorByName('name', $this->t('Message should be at least 10 characters.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->messenger()->addStatus($this->t('The message has been sent.'));
    $form_state->setRedirect('<front>');
  }

}
