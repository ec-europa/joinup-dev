<?php

namespace Drupal\joinup_user\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\og\MembershipManagerInterface;
use Drupal\og\OgMembershipInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Form that allowes the user to unsubscribe from all groups.
 */
class UnsubscribeFromAllGroupsForm extends ConfirmFormBase {

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
   * The renderer service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * The user entity from the route.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * Constructs a UnsubscribeFromAllGroupsForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\og\MembershipManagerInterface $membership_manager
   *   The membership manager service.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   The renderer service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MembershipManagerInterface $membership_manager, Renderer $renderer) {
    $this->entityTypeManager = $entity_type_manager;
    $this->membershipManager = $membership_manager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('og.membership_manager'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'unsubscribe_from_all_groups_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Unsubscribe from all groups');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Are you sure you want to unsubscribe from all groups?<br />You will stop receiving news and updates from all groups, including those you are a facilitator in.');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('joinup_subscription.subscriptions_page');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, AccountInterface $user = NULL) {
    $this->user = $user;
    if ($memberships = $this->getUserMembershipIds()) {
      $memberships = $this->entityTypeManager->getStorage('og_membership')->loadMultiple($memberships);
      $labels = array_map(function (OgMembershipInterface $membership) {
        return '[' . $membership->getGroupBundle() . '] ' . $membership->getGroup()->label();
      }, $memberships);
      asort($labels);
      $form = parent::buildForm($form, $form_state);
      $form['information'] = [
        '#type' => 'item',
        '#markup' => t('You are currently registered to be notified for the following groups:'),
        '#tree' => TRUE,
        'items' => [
          '#theme' => 'item_list',
          '#items' => $labels,
        ],
      ];
    }
    else {
      $form['help'] = [
        '#type' => 'item',
        '#markup' => t('You do not have any groups to unsubscribe from.'),
      ];

      $form['return'] = [
        '#type' => 'submit',
        '#value' => $this->t('Return'),
        '#submit' => ['::returnSubmit'],
      ];
    }
    return $form;
  }

  /**
   * Submit callback for the return button.
   *
   * Returns the user to the same destination as the cancel URL.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function returnSubmit(array &$form, FormStateInterface $form_state): void {
    $return_url = $this->getCancelUrl();
    $form_state->setRedirect($return_url->getRouteName(), $return_url->getRouteParameters());
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $membership_ids = $this->getUserMembershipIds();
    $redirect_url = $this->getCancelUrl();
    $form_state->setRedirect($redirect_url->getRouteName(), $redirect_url->getRouteParameters());

    $operations = [];
    foreach ($membership_ids as $membership_id) {
      $operations[] = [
        '\Drupal\joinup_user\Form\UnsubscribeFromAllGroupsForm::membershipUnsubscribe',
        [$membership_id],
      ];
    }

    $batch = [
      'title' => t('Unsubscribe from groups'),
      'operations' => $operations,
      'finished' => [$this, 'membershipUnsubscribeFinish'],
      'init_message' => t('Initiating...'),
      'progress_message' => t('Processed @current out of @total. Estimated time: @estimate.'),
    ];

    batch_set($batch);
  }

  /**
   * Unsubscribes from a group.
   *
   * @param int $membership_id
   *   The membership ID.
   * @param array $context
   *   The batch context array.
   */
  public static function membershipUnsubscribe(int $membership_id, &$context): void {
    /** @var \Drupal\og\OgMembershipInterface $membership */
    $membership = \Drupal::entityTypeManager()->getStorage('og_membership')->load($membership_id);
    $membership->set('subscription_bundles', []);
    $membership->save();
    $context['results'][] = t('[:bundle] %title', [
      ':bundle' => $membership->getGroupBundle(),
      '%title' => $membership->getGroup()->label(),
    ]);
  }

  /**
   * Implements callback_batch_finished.
   *
   * @param bool $success
   *   A boolean indicating whether the batch has completed successfully.
   * @param array $results
   *   The value set in $context['results'] by callback_batch_operation().
   * @param array $operations
   *   If $success is FALSE, contains the operations that remained unprocessed.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   */
  public function membershipUnsubscribeFinish(bool $success, array $results, array $operations): RedirectResponse {
    // @see \callback_batch_finished.
    if ($success) {
      $list = [
        '#theme' => 'item_list',
        '#items' => $results,
      ];
      $arguments = [
        '@count' => count($results),
        '@items' => $this->renderer->render($list),
      ];
      $message = $this->t('You will not receive notification for the following @count items groups.<br />@items', $arguments);
      $this->messenger()->addStatus($message);
    }
    else {
      // $operations contains the operations that remained unprocessed.
      $error_operation = reset($operations);
      $message = $this->t('An error occurred while processing %error_operation with arguments: @arguments', [
        '%error_operation' => $error_operation[0],
        '@arguments' => print_r($error_operation[1], TRUE),
      ]);
      $this->messenger()->addError($message);
    }

    $url = $this->getCancelUrl();
    return new RedirectResponse(Url::fromRoute($url->getRouteName(), $url->getRouteParameters())->setAbsolute()->toString());
  }

  /**
   * Access check for the UnsubscribeFromAllGroupsForm.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user from the route.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result object.
   */
  public function access(AccountInterface $account) {
    /** @var \Drupal\Core\Session\AccountProxyInterface $account_proxy */
    $account_proxy = \Drupal::service('current_user');
    // Deny access if the user is not logged in.
    if ($account_proxy->isAnonymous()) {
      return AccessResult::forbidden();
    }

    return AccessResult::allowedIf($account->id() === $account_proxy->id());
  }

  /**
   * Returns an array of membership ids that the user has active subscriptions.
   *
   * @return \Drupal\og\OgMembershipInterface[]
   *   An array of memberships.
   */
  protected function getUserMembershipIds(): array {
    $query = $this->entityTypeManager
      ->getStorage('og_membership')
      ->getQuery()
      ->condition('uid', $this->user->id())
      ->exists('subscription_bundles');
    return $query->execute();
  }

}
