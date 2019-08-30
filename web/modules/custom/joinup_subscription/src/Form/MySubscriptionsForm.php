<?php

declare(strict_types = 1);

namespace Drupal\joinup_subscription\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\joinup_community_content\CommunityContentHelper;
use Drupal\joinup_core\JoinupRelationManagerInterface;
use Drupal\joinup_core\Plugin\Field\FieldType\EntityBundlePairItem;
use Drupal\og\MembershipManagerInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Joinup subscription form.
 */
class MySubscriptionsForm extends FormBase {

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
   * The OG membership manager.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $membershipManager;

  /**
   * Constructs a new MySubscriptionsForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entityTypeBundleInfo
   *   The entity type bundle info service.
   * @param \Drupal\joinup_core\JoinupRelationManagerInterface $relationManager
   *   The Joinup relation manager service.
   * @param \Drupal\og\MembershipManagerInterface $membershipManager
   *   The OG membership manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, EntityTypeBundleInfoInterface $entityTypeBundleInfo, JoinupRelationManagerInterface $relationManager, MembershipManagerInterface $membershipManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
    $this->relationManager = $relationManager;
    $this->membershipManager = $membershipManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('joinup_core.relations_manager'),
      $container->get('og.membership_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'joinup_subscription_my_subscriptions';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, AccountInterface $user = NULL) {
    // When no user is passed we cannot show anything useful.
    if (empty($user)) {
      throw new \InvalidArgumentException('No user account supplied.');
    }
    $user = $this->entityTypeManager->getStorage('user')->load($user->id());

    $form['description'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Set your preferences to receive notifications on a per collection basis.'),
    ];

    $this->loadUserSubscriptionFrequencyWidget($form, $form_state, $user);
    $memberships = $this->relationManager->getUserGroupMembershipsByBundle($user, 'rdf_entity', 'collection');
    $bundle_info = $this->entityTypeBundleInfo->getBundleInfo('node');

    // Add a JS behavior to enable the buttons when the checkboxes or the
    // dropdown on the form are toggled.
    $form['collections']['#attached']['library'][] = 'joinup_subscription/my_subscriptions';

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

    $memberships_with_subscription = FALSE;
    foreach ($memberships as $membership) {
      $collection = $membership->getGroup();
      if ($collection === NULL) {
        continue;
      }
      $clean_collection_id = Html::cleanCssIdentifier($collection->id());
      $form['collections'][$collection->id()] = [
        '#type' => 'container',
        '#id' => 'collection-' . $clean_collection_id,
        '#attributes' => [
          'class' => ['collection-subscription'],
        ],
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
        if (!$memberships_with_subscription && $value) {
          $memberships_with_subscription = TRUE;
        }
        $form['collections'][$collection->id()]['bundles'][$bundle_id] = [
          '#type' => 'checkbox',
          '#title' => $bundle_info[$bundle_id]['label'],
          '#return_value' => TRUE,
          '#default_value' => $value,
          // Make sure to turn autocomplete off so that the browser doesn't try
          // to restore a half submitted form when the user does a soft reload.
          '#attributes' => ['autocomplete' => 'off'],
        ];
      }

      $form['collections'][$collection->id()]['bundles']['submit'] = [
        '#ajax' => [
          'callback' => '::reloadCollection',
          'wrapper' => 'collection-' . $clean_collection_id,
        ],
        '#name' => 'submit-' . $clean_collection_id,
        '#submit' => ['::submitForm'],
        '#type' => 'submit',
        '#value' => $this->t('Save changes'),
        '#attributes' => [
          // The button should appear disabled initially. It becomes enabled
          // when the user changes one of the checkboxes. We have to set this
          // HTML attribute directly instead of using the `#disabled` property
          // because this will make Drupal ignore the form submissions.
          'disabled' => 'disabled',
          // Make sure to turn autocomplete off so that the browser doesn't try
          // to restore a half submitted form when the user does a soft reload.
          'autocomplete' => 'off',
        ],
      ];
    }

    $form['unsubscribe_all'] = [
      '#type' => 'link',
      '#title' => $this->t('Unsubscribe from all'),
      '#url' => Url::fromRoute('joinup_subscription.unsubscribe_all', [
        'user' => $user->id(),
      ]),
      '#access' => $memberships_with_subscription,
      '#attributes' => ['class' => 'featured__form-button button button--blue-light mdl-button mdl-js-button mdl-button--raised mdl-js-ripple-effect mdl-button--accent'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $collection_id = $this->getTriggeringElementCollectionId($form_state);
    $collection = $this->entityTypeManager->getStorage('rdf_entity')->load($collection_id);
    $user = $form_state->getBuildInfo()['args'][0];
    $membership = $this->membershipManager->getMembership($collection, $user);

    // Check if the subscriptions have changed. This allows us to skip saving
    // the membership entity if nothing changed.
    $subscribed_bundles = array_keys(array_filter($form_state->getValue('collections')[$membership->getGroupId()]['bundles']));

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

  /**
   * Submit callback for the frequency settings.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function submitUserFrequency(array &$form, FormStateInterface $form_state) {
    $account = $form_state->getBuildInfo()['args'][0];
    $user = $this->entityTypeManager->getStorage('user')->load($account->id());

    $value = $form_state->getValue('field_user_frequency');
    $user->set('field_user_frequency', $value);
    $user->save();
  }

  /**
   * AJAX callback that refreshes the user frequency settings.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The render array containing the updated frequency settings.
   */
  public function reloadFrequency(array &$form, FormStateInterface $form_state): array {
    $form['user_subscription_settings']['field_user_frequency']['submit']['#value'] = $this->t('Saved!');
    return $form['user_subscription_settings'];
  }

  /**
   * AJAX callback that refreshes a collection after it has been submitted.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The render array containing the updated collection to refresh.
   */
  public function reloadCollection(array &$form, FormStateInterface $form_state): array {
    $submitted_collection_id = $this->getTriggeringElementCollectionId($form_state);
    $form['collections'][$submitted_collection_id]['bundles']['submit']['#value'] = $this->t('Saved!');
    return $form['collections'][$submitted_collection_id];
  }

  /**
   * Returns the collection ID for the submit button that was clicked.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the submitted form.
   *
   * @return string
   *   The collection ID that corresponds to the submit button that was clicked.
   */
  protected function getTriggeringElementCollectionId(FormStateInterface $form_state): string {
    // Return the collection ID which is stored in the third to last parent of
    // the button: `['collections'][$collection_id]['bundles']['submit']`.
    $clicked_button_parents = array_values($form_state->getTriggeringElement()['#parents']);
    return $clicked_button_parents[count($clicked_button_parents) - 3];
  }

  /**
   * Loads the field_user_frequency widget and prepares a subform with it.
   *
   * @param array $form
   *   The parent form array.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The parent form state.
   * @param \Drupal\user\UserInterface $user
   *   The user object.
   */
  protected function loadUserSubscriptionFrequencyWidget(array &$form, FormStateInterface $formState, UserInterface $user): void {
    $form['user_subscription_settings'] = [
      '#type' => 'container',
      '#tree' => TRUE,
      '#id' => 'user-frequency',
      '#parents' => [],
    ];
    $subform_state = SubformState::createForSubform($form['user_subscription_settings'], $form, $formState);

    /** @var \Drupal\Core\Entity\Entity\EntityFormDisplay $form_display */
    $form_display = $this->entityTypeManager->getStorage('entity_form_display')->load('user.user.subscription_settings');
    $subform_state->set('entity', $user);
    $subform_state->set('form_display', $form_display);

    /** @var \Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsButtonsWidget $widget */
    $widget = $form_display->getRenderer('field_user_frequency');
    $items = $user->get('field_user_frequency');
    $items->filterEmptyItems();

    $form['user_subscription_settings']['field_user_frequency'] = $widget->form($items, $form['user_subscription_settings'], $subform_state);
    $form['user_subscription_settings']['field_user_frequency']['#access'] = $items->access('edit');

    $form['user_subscription_settings']['field_user_frequency']['submit'] = [
      '#ajax' => [
        'callback' => '::reloadFrequency',
        'wrapper' => 'user-frequency',
      ],
      '#submit' => ['::submitUserFrequency'],
      '#type' => 'submit',
      '#value' => $this->t('Save changes'),
      '#attributes' => [
        // The button should appear disabled initially. It becomes enabled
        // when the user changes one of the checkboxes. We have to set this
        // HTML attribute directly instead of using the `#disabled` property
        // because this will make Drupal ignore the form submissions.
        'disabled' => 'disabled',
        // Make sure to turn autocomplete off so that the browser doesn't try
        // to restore a half submitted form when the user does a soft reload.
        'autocomplete' => 'off',
      ],
    ];
  }

}
