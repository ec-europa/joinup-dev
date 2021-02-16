<?php

declare(strict_types = 1);

namespace Drupal\joinup_subscription\Form;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\user\UserInterface;
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
   * The renderer service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

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
   * @param \Drupal\Core\Render\Renderer $renderer
   *   The renderer service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info
   *   The entity type bundle info service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Renderer $renderer, EntityTypeBundleInfoInterface $bundle_info) {
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
    $this->bundleInfo = $bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('renderer'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'unsubscribe_from_all_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion(): MarkupInterface {
    return $this->t('Unsubscribe from all?');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): MarkupInterface {
    return $this->t('Are you sure you want to unsubscribe from all collections and/or solutions? You will stop receiving news and updates, including the pending memberships, from the following:');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return Url::fromRoute('joinup_subscription.my_subscriptions');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, UserInterface $user = NULL): array {
    $form_state->set('#user', $user);
    if ($memberships_ids = $this->getUserMembershipIds($user)) {
      $memberships = $this->entityTypeManager->getStorage('og_membership')->loadMultiple($memberships_ids);
      $labels = [];
      foreach ($memberships as $membership) {
        if ($group = $membership->getGroup()) {
          $labels[$group->bundle()][] = $group->label();
        }
      }
      $form = parent::buildForm($form, $form_state);
      $form += $this->getGroupBuild($labels);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $user = $form_state->get('#user');
    $membership_ids = $this->getUserMembershipIds($user);
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
      'title' => $this->t('Unsubscribe from all'),
      'operations' => $operations,
      'finished' => [$this, 'membershipUnsubscribeFinish'],
      'init_message' => $this->t('Initiating...'),
      'progress_message' => $this->t('Processed @current out of @total.'),
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
    $group = $membership->getGroup();
    if (!$group) {
      // Skip in case of an orphaned membership. The membership will be deleted
      // on cron run.
      return;
    }
    $membership->set('subscription_bundles', []);
    $membership->save();
    $context['results'][$group->bundle()][] = $group->label();
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
      $list = $this->getGroupBuild($results);
      $arguments = [
        '@items' => $this->renderer->render($list),
      ];
      $message = $this->t('You will no longer receive notifications for the following:<br />@items', $arguments);
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
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\user\UserInterface $user
   *   The user being unsubscribed.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result object.
   */
  public function access(AccountInterface $current_user, UserInterface $user): AccessResultInterface {
    // Deny access if the user is not logged in.
    if ($current_user->isAnonymous()) {
      return AccessResult::forbidden();
    }

    $cache_tags = $this->entityTypeManager->getDefinition('og_membership')->getListCacheTags();
    $has_subscriptions = (bool) $this->getUserMembershipIds($user);
    $access_result = AccessResult::allowedIf($has_subscriptions)->addCacheTags($cache_tags);
    // If the use has no membership, deny the access.
    if (!$access_result->isAllowed()) {
      return $access_result;
    }

    // User admins are allowed.
    $access_result->andIf(AccessResult::allowedIfHasPermission($current_user, 'administer users'));
    if ($access_result->isAllowed()) {
      return $access_result;
    }

    // Users can unsubscribe from their subscriptions.
    return $access_result->orIf(AccessResult::allowedIf($user->id() == $current_user->id()));
  }

  /**
   * Returns an array of membership IDs that the user has active subscriptions.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user being unsubscribed.
   *
   * @return int[]
   *   An array of membership IDs.
   */
  protected function getUserMembershipIds(UserInterface $user): array {
    return $this->entityTypeManager
      ->getStorage('og_membership')
      ->getQuery()
      ->condition('uid', $user->id())
      ->exists('subscription_bundles')
      ->execute();
  }

  /**
   * Builds an item list of group labels.
   *
   * @param array $labels
   *   Group labels, grouped by group type.
   *
   * @return array
   *   A item list render array.
   */
  protected function getGroupBuild(array $labels): array {
    array_walk($labels, function (array &$group_labels): void {
      asort($group_labels);
    });

    $build = [];
    foreach ($labels as $bundle => $group_labels) {
      $build[$bundle] = [
        '#theme' => 'item_list',
        '#title' => $this->bundleInfo->getBundleCountLabel('rdf_entity', $bundle, count($group_labels), 'no_count_capitalize'),
        '#items' => $group_labels,
      ];
    }

    return $build;
  }

}
