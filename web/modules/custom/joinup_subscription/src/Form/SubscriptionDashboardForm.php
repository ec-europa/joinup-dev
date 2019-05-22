<?php

namespace Drupal\joinup_subscription\Form;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\joinup_community_content\CommunityContentHelper;
use Drupal\joinup_core\JoinupRelationManagerInterface;
use Drupal\joinup_core\Plugin\Field\FieldType\EntityBundlePairItem;
use Drupal\joinup_subscription\JoinupSubscriptionInterface;
use Drupal\og\OgMembershipInterface;
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
   * Constructs a new SubscriptionDashboardForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entityTypeBundleInfo
   *   The entity type bundle info service.
   * @param \Drupal\joinup_core\JoinupRelationManagerInterface $relationManager
   *   The Joinup relation manager service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, EntityTypeBundleInfoInterface $entityTypeBundleInfo, JoinupRelationManagerInterface $relationManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
    $this->relationManager = $relationManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
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
    $memberships_with_subscription = array_filter($memberships, function (OgMembershipInterface $membership): array {
      return $membership->get('subscription_bundles')->getValue();
    });
    $bundle_info = $this->entityTypeBundleInfo->getBundleInfo('node');

    $form['unsubscribe_all'] = [
      '#type' => 'link',
      '#title' => t('Unsubscribe from all'),
      '#url' => Url::fromRoute('joinup_subscription.unsubscribe_all', [
        'user' => $user->id(),
      ]),
      '#access' => !empty($memberships_with_subscription),
      '#attributes' => ['class' => 'featured__form-button button button--blue-light mdl-button mdl-js-button mdl-button--raised mdl-js-ripple-effect mdl-button--accent'],
    ];

    $form['description'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Set your preferences to receive notifications on a per collection basis.'),
    ];

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

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

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

}
