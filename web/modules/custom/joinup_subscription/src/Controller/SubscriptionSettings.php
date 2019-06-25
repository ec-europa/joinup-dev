<?php

namespace Drupal\joinup_subscription\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\joinup_community_content\CommunityContentHelper;
use Drupal\joinup_core\JoinupRelationManagerInterface;
use Drupal\joinup_core\Plugin\Field\FieldType\EntityBundlePairItem;
use Drupal\joinup_subscription\JoinupSubscriptionInterface;
use Drupal\og\OgMembershipInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountProxy;

/**
 * Controller that shows the subscription settings form.
 */
class SubscriptionSettings extends ControllerBase {

  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The Joinup relation manager service.
   *
   * @var \Drupal\joinup_core\JoinupRelationManagerInterface
   */
  protected $relationManager;

  /**
   * Constructs a new SubscriptionSettings form.
   *
   * @param \Drupal\Core\Session\AccountProxy $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entityTypeBundleInfo
   *   The entity type bundle info service.
   * @param \Drupal\joinup_core\JoinupRelationManagerInterface $relationManager
   *   The Joinup relation manager service.
   */
  public function __construct(AccountProxy $current_user, EntityTypeManagerInterface $entityTypeManager, EntityTypeBundleInfoInterface $entityTypeBundleInfo, JoinupRelationManagerInterface $relationManager) {
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entityTypeManager;
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
    $this->relationManager = $relationManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('joinup_core.relations_manager')
    );
  }

  /**
   * Builds the subscription_settings form for the user element.
   *
   * @return array
   *   The form array.
   */
  public function build($user) {
    $form = $this->entityFormBuilder()->getForm($user, 'subscription_settings');

    $memberships = $this->relationManager->getUserGroupMembershipsByBundle($user, 'rdf_entity', 'collection');
    $memberships_with_subscription = array_filter($memberships, function (OgMembershipInterface $membership): array {
      return $membership->get('subscription_bundles')->getValue();
    });
    $bundle_info = $this->entityTypeBundleInfo->getBundleInfo('node');

    $form['unsubscribe_all'] = [
      '#type' => 'link',
      '#title' => $this->t('Unsubscribe from all'),
      '#url' => Url::fromRoute('joinup_subscription.unsubscribe_all', [
        'user' => $user->id(),
      ]),
      '#access' => !empty($memberships_with_subscription),
      '#attributes' => ['class' => 'featured__form-button button button--blue-light mdl-button mdl-js-button mdl-button--raised mdl-js-ripple-effect mdl-button--accent'],
      '#weight' => 0,
    ];

    $form['description'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Set your preferences to receive notifications on a per collection basis.'),
      '#weight' => 0,
    ];

    $form['field_user_frequency']['#weight'] = 1;

    // Return early if there are no memberships to display.
    if (!(bool) count($memberships)) {
      $empty_message = $this->t('No collection memberships yet. Join one or more collections to subscribe to their content!');
      $form['empty_text'] = [
        '#theme' => 'status_messages',
        '#message_list' => ['status' => [$empty_message]],
        '#status_headings' => [
          'status' => $this->t('Status message'),
          'error' => $this->t('Error message'),
          'warning' => $this->t('Warning message'),
        ],
      ];
      return $form;
    }

    $form['collections']['#tree'] = TRUE;
    $form['collections']['#weight'] = 2;

    foreach ($memberships as $membership) {
      $collection = $membership->getGroup();
      if ($collection === NULL) {
        continue;
      }
      $form['collections'][$collection->id()] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['collection-subscription']],
        'preview' => $this->entityTypeManager->getViewBuilder($collection->getEntityTypeId())->view($collection, 'list_view'),
        'bundles' => [
          '#type' => 'container',
          '#extra_suggestion' => 'container__subscribe_form',
        ],
      ];

      foreach (CommunityContentHelper::BUNDLES as $bundle_id) {
        $subscription_bundles = $membership->get('subscription_bundles')->getIterator()->getArrayCopy();
        $value = array_reduce($subscription_bundles, function (bool $carry, EntityBundlePairItem $entity_bundle_pair) use ($bundle_id): bool {
          return $carry || $entity_bundle_pair->getBundleId() === $bundle_id;
        }, FALSE);
        $form['collections'][$collection->id()]['bundles'][$bundle_id] = [
          '#type' => 'select',
          '#title' => $bundle_info[$bundle_id]['label'],
          '#options' => [
            JoinupSubscriptionInterface::SUBSCRIBE_ALL => $this->t('All notifications'),
            // @todo Add support for `::SUBSCRIBE_NEW` -> "Only new content".
            // @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-4980
            JoinupSubscriptionInterface::SUBSCRIBE_NONE => $this->t('No notifications'),
          ],
          '#default_value' => $value ? JoinupSubscriptionInterface::SUBSCRIBE_ALL : JoinupSubscriptionInterface::SUBSCRIBE_NONE,
        ];
      }
    }

    $form['actions']['submit']['#submit'][] = '::submitForm';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $user = $form_state->getBuildInfo()['args'][0];
    $memberships = $this->relationManager->getUserGroupMembershipsByBundle($user, 'rdf_entity', 'collection');
    foreach ($memberships as $membership) {
      // Check if the subscriptions have changed. This allows us to skip saving
      // the membership entity if nothing changed.
      $subscribed_bundles = array_keys(array_filter($form_state->getValue('collections')[$membership->getGroupId()]['bundles'], function (string $subscription_type): bool {
        return $subscription_type === JoinupSubscriptionInterface::SUBSCRIBE_ALL;
      }));

      $original_bundles = array_map(function (array $item): string {
        return $item['bundle'];
      }, $membership->get('subscription_bundles')->getValue());

      sort($subscribed_bundles);
      sort($original_bundles);
      if ($subscribed_bundles !== $original_bundles) {
        // Bundle subscriptions have changed, update the membership.
        $membership->set('subscription_bundles', array_map(function (string $bundle): array {
          return ['entity_type' => 'node', 'bundle' => $bundle];
        }, $subscribed_bundles))->save();
      }
    }
    $this->messenger()->addStatus($this->t('The subscriptions have been updated.'));
  }

  /**
   * Access control for the subscription settings user page.
   *
   * The user is checked for both global permissions and permissions to edit
   * their own subscriptions.
   *
   * @param \Drupal\Core\Entity\EntityInterface $user
   *   The user object from the route.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   An access result object carrying the result of the check.
   */
  public function access(EntityInterface $user) {
    // Users that can administer all users have access.
    if ($this->currentUser->hasPermission('administer users')) {
      return AccessResult::allowed();
    }
    // The logged in user can manage their own subscriptions.
    elseif (!$this->currentUser->isAnonymous() && $this->currentUser->id() == $user->id()) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

  /**
   * Redirects the currently logged in user to their subscription settings form.
   *
   * This controller assumes that it is only invoked for authenticated users.
   * This is enforced for the 'joinup_subscription.subscription_settings_page'
   * route with the '_user_is_logged_in' requirement.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Returns a redirect to the subscription settings form of the currently
   *   logged in user.
   */
  public function subscriptionSettingsPage() {
    return $this->redirect('joinup_subscription.subscription_settings', ['user' => $this->currentUser()->id()]);
  }

  /**
   * Displays the subscription dashboard for the currently logged in user.
   *
   * This controller assumes that it is only invoked for authenticated users.
   * This is enforced for the 'joinup_subscription.subscriptions_page' route
   * with the '_user_is_logged_in' requirement.
   *
   * @return array
   *   The subscription dashboard form array.
   */
  public function subscriptionDashboardPage() {
    return $this->formBuilder()->getForm('Drupal\joinup_subscription\Form\SubscriptionDashboardForm', $this->currentUser());
  }

}
