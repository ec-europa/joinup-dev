<?php

declare(strict_types = 1);

namespace Drupal\joinup_subscription\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
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
 * Form that allows the user to unsubscribe from all groups.
 */
class UnsubscribeFromAllForm extends ConfirmFormBase {

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
   * The membership group bundle.
   *
   * @var string
   */
  protected $bundle;

  /**
   * Constructs an UnsubscribeFromAllForm.
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
    return 'unsubscribe_from_all_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Unsubscribe from all :bundles', [
      ':bundle' => $this->bundle,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->bundle === 'collection'
      ? $this->t('Are you sure you want to unsubscribe from all collections?<br />You will stop receiving news and updates from all collections (including the pending memberships).<br />In the future you will not be notified for the following collections:')
      : $this->t('Are you sure you want to unsubscribe from all solutions?<br />You will stop receiving news and updates from all solutions.<br />In the future you will not be notified for the following solutions:');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('joinup_subscription.my_subscriptions', [
      'subscription_type' => $this->bundle,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?string $bundle = NULL) {
    $this->bundle = $bundle;
    if ($memberships_ids = $this->getUserMembershipIds($this->bundle)) {
      $memberships = $this->entityTypeManager->getStorage('og_membership')->loadMultiple($memberships_ids);
      $labels = array_map(function (OgMembershipInterface $membership): ?string {
        $group = $membership->getGroup();
        // Handle also orphaned memberships not deleted yet.
        return $group ? $group->label() : NULL;
      }, $memberships);
      $labels = array_filter($labels);
      asort($labels);
      $form = parent::buildForm($form, $form_state);
      $form['collections'] = [
        '#theme' => 'item_list',
        '#items' => $labels,
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $membership_ids = $this->getUserMembershipIds($this->bundle);
    $redirect_url = $this->getCancelUrl();
    $form_state->setRedirect($redirect_url->getRouteName(), $redirect_url->getRouteParameters());

    $operations = [];
    foreach ($membership_ids as $membership_id) {
      $operations[] = [
        UnsubscribeFromAllForm::class . '::membershipUnsubscribe',
        [$membership_id],
      ];
    }

    $batch = [
      'title' => $this->t('Unsubscribe from :bundles', [
        ':bundle' => $this->bundle,
      ]),
      'operations' => $operations,
      'finished' => [$this, 'membershipUnsubscribeFinish'],
      'init_message' => $this->t('Initiating...'),
      'progress_message' => $this->t('Processed @current out of @total. Estimated time: @estimate.'),
    ];

    batch_set($batch);
  }

  /**
   * Unsubscribes from a collection.
   *
   * @param int $membership_id
   *   The membership ID.
   * @param array $context
   *   The batch context array.
   */
  public static function membershipUnsubscribe(int $membership_id, array &$context): void {
    /** @var \Drupal\og\OgMembershipInterface $membership */
    $membership = \Drupal::entityTypeManager()->getStorage('og_membership')->load($membership_id);
    if (empty($membership->getGroup())) {
      // Skip in case of an orphaned membership. The membership will be deleted
      // on cron run.
      return;
    }
    $membership->set('subscription_bundles', []);
    $membership->save();
    $context['results'][] = t('%title', [
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
    // @see \callback_batch_finished()
    if ($success) {
      $list = [
        '#theme' => 'item_list',
        '#items' => $results,
      ];
      $count = count($results);
      $arguments = [
        '@count' => $count,
        ':bundle' => $this->bundle,
        '@items' => $this->renderer->render($list),
      ];
      $message = $this->formatPlural($count, 'You will no longer receive notifications for the following :bundle:<br />@items', 'You will no longer receive notifications for the following @count :bundles:<br />@items', $arguments);
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
   * Access check for the UnsubscribeFromAllCollectionsForm.
   *
   * @param \Drupal\Core\Session\AccountInterface $account_proxy
   *   The user from the route.
   * @param string $bundle
   *   The bundle of the group.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result object.
   */
  public function access(AccountInterface $account_proxy, string $bundle): AccessResultInterface {
    // Deny access if the user is not logged in.
    if ($account_proxy->isAnonymous()) {
      return AccessResult::forbidden();
    }

    return AccessResult::allowedIf($this->getUser()->id() === $account_proxy->id() && $this->getUserMembershipIds($bundle));
  }

  /**
   * Returns an array of membership IDs that the user has active subscriptions.
   *
   * @param string $bundle
   *   The bundle of the group.
   *
   * @return int[]
   *   An array of memberships.
   */
  protected function getUserMembershipIds(string $bundle): array {
    $query = $this->entityTypeManager
      ->getStorage('og_membership')
      ->getQuery()
      ->condition('uid', $this->getUser()->id())
      ->condition('entity_bundle', $bundle)
      ->exists('subscription_bundles');
    return $query->execute();
  }

  /**
   * Returns the user entity from the route.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The user account.
   */
  protected function getUser(): AccountInterface {
    return $this->getRouteMatch()->getParameter('user');
  }

}
