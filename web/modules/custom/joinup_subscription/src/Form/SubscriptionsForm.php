<?php

declare(strict_types = 1);

namespace Drupal\joinup_subscription\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\joinup_group\JoinupGroupManagerInterface;
use Drupal\joinup_subscription\JoinupSubscriptionsHelper;
use Drupal\joinup_subscription\Plugin\Field\FieldType\EntityBundlePairItem;
use Drupal\og\MembershipManagerInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Joinup subscription form.
 */
class SubscriptionsForm extends FormBase {

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
   * The Joinup group manager service.
   *
   * @var \Drupal\joinup_group\JoinupGroupManagerInterface
   */
  protected $groupManager;

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
   * @param \Drupal\joinup_group\JoinupGroupManagerInterface $groupManager
   *   The Joinup group manager service.
   * @param \Drupal\og\MembershipManagerInterface $membershipManager
   *   The OG membership manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, EntityTypeBundleInfoInterface $entityTypeBundleInfo, JoinupGroupManagerInterface $groupManager, MembershipManagerInterface $membershipManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
    $this->groupManager = $groupManager;
    $this->membershipManager = $membershipManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('joinup_group.group_manager'),
      $container->get('og.membership_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'joinup_subscription_my_subscriptions';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?AccountInterface $user = NULL): array {
    $user = $user ?: $this->currentUser();
    if (!$user instanceof UserInterface) {
      $user = $this->entityTypeManager->getStorage('user')->load($user->id());
    }
    $form_state->set('#user', $user);

    $this->loadUserSubscriptionFrequencyWidget($form, $form_state, $user);

    $form['groups']['#tree'] = TRUE;
    foreach (JoinupSubscriptionsHelper::SUBSCRIPTION_BUNDLES as $type => $subscription_bundles) {
      $memberships = $this->groupManager->getUserGroupMembershipsByBundle($user, 'rdf_entity', $type);
      $form['groups'][$type]['label'] = [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => $this->entityTypeBundleInfo->getBundleCountLabel('rdf_entity', $type, count($memberships), 'no_count_capitalize'),
        '#attributes' => [
          'class' => ['subscription-type'],
        ],
      ];

      // Add a JS behavior to enable the buttons when the checkboxes or the
      // dropdown on the form are toggled.
      $form['groups']['#attached']['library'][] = 'joinup_subscription/subscriptions';

      // Return early if there are no memberships to display.
      if (!$memberships) {
        $type_info = $this->entityTypeBundleInfo->getBundleInfo('rdf_entity')[$type];
        $empty_message = $this->t('No @group memberships yet. Join one or more @groups to subscribe to their content!', [
          '@group' => $type_info['label_singular'],
          '@groups' => $type_info['label_plural'],
        ]);
        $form['groups'][$type]['empty_text'] = [
          '#theme' => 'status_messages',
          '#message_list' => ['status' => [$empty_message]],
          '#status_headings' => [
            'status' => $this->t('Status message'),
            'error' => $this->t('Error message'),
            'warning' => $this->t('Warning message'),
          ],
        ];
        continue;
      }

      // Generate the list of memberships with checkboxes to choose which
      // bundles to subscribe to.
      $bundle_info = [];
      foreach (array_keys($subscription_bundles) as $entity_type_id) {
        $bundle_info[$entity_type_id] = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);
      }

      // Keep track of the groups with subscriptions in order to properly show
      // or hide the 'Unsubscribe from all' button in the end of the page.
      foreach ($memberships as $membership) {
        /** @var \Drupal\joinup_group\Entity\GroupInterface $group */
        $group = $membership->getGroup();
        if ($group === NULL) {
          continue;
        }
        $clean_group_id = Html::cleanCssIdentifier($group->id());
        $form['groups'][$group->id()] = [
          '#type' => 'container',
          '#id' => 'group-' . $clean_group_id,
          '#attributes' => [
            'class' => ['group-subscription'],
          ],
          'logo' => $group->getLogoAsRenderArray([
            'label' => 'hidden',
            'type' => 'image',
            'settings' => [
              'image_style' => 'vertical_medium_image',
              'image_link' => 'content',
            ],
          ]),
          'link' => [
            '#type' => 'link',
            '#title' => $group->label(),
            '#url' => $group->toUrl(),
          ],
          'motivation' => [
            '#markup' => $this->t('Send me notifications for:'),
          ],
          'bundles' => [
            '#type' => 'container',
            '#extra_suggestion' => 'container__subscribe_form',
          ],
          '#extra_suggestion' => 'container__group_subscription',
        ];

        $subscription_status = [];

        $active_subscription_bundles = $membership->get('subscription_bundles')->getIterator()->getArrayCopy();
        foreach ($subscription_bundles as $entity_type_id => $bundle_ids) {
          foreach ($bundle_ids as $bundle_id) {
            $key = static::getSubscriptionKey($entity_type_id, $bundle_id);
            $value = array_reduce($active_subscription_bundles, function (bool $carry, EntityBundlePairItem $entity_bundle_pair) use ($entity_type_id, $bundle_id): bool {
              return $carry || $entity_bundle_pair->getEntityTypeId() === $entity_type_id && $entity_bundle_pair->getBundleId() === $bundle_id;
            }, FALSE);

            $form['groups'][$group->id()]['bundles'][$key] = [
              '#type' => 'checkbox',
              '#title' => $bundle_info[$entity_type_id][$bundle_id]['label'],
              '#return_value' => TRUE,
              '#default_value' => $value,
              // Make sure to turn autocomplete off so that the browser doesn't
              // try to restore a half submitted form when the user does a soft
              // reload.
              '#attributes' => ['autocomplete' => 'off'],
            ];

            // Store status of checkboxes.
            $subscription_status[$key] = $value;
          }
        }

        $form['groups'][$group->id()]['bundles']['submit'] = [
          '#ajax' => [
            'callback' => '::reloadGroup',
          ],
          '#name' => 'submit-' . $clean_group_id,
          '#submit' => ['::submitForm'],
          '#type' => 'submit',
          '#extra_suggestion' => 'subscribe_save',
          '#value' => $this->t('Save changes'),
          '#group_type' => $type,
          '#attributes' => [
            // The button should appear disabled initially. It becomes enabled
            // when the user changes one of the checkboxes. We have to set this
            // HTML attribute directly instead of using the `#disabled` property
            // because this will make Drupal ignore the form submissions.
            'disabled' => 'disabled',
            // Make sure to turn autocomplete off so that the browser doesn't
            // try to restore a half submitted form when the user does a soft
            // reload.
            'autocomplete' => 'off',
            // Store the initial state of the subscriptions so that we can
            // unlock the save button in JS whenever a state changes.
            'data-drupal-subscriptions' => Json::encode(array_values($subscription_status)),
          ],
        ];
      }

    }
    $form['edit-actions'] = [
      '#type' => 'container',
      '#id' => 'edit-actions',
      '#attributes' => ['class' => 'form__subscribe-actions'],
    ];

    $unsubscribe_all_url = Url::fromRoute(
      'joinup_subscription.unsubscribe_all',
      ['user' => $user->id()]
    );
    $form['edit-actions']['unsubscribe_all'] = [
      '#type' => 'link',
      '#title' => $this->t('Unsubscribe from all'),
      '#url' => $unsubscribe_all_url,
      '#access' => $unsubscribe_all_url->access(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $group_id = $this->getTriggeringElementGroupId($form_state);
    $group = $this->entityTypeManager->getStorage('rdf_entity')->load($group_id);
    $user = $form_state->get('#user');
    $membership = $this->membershipManager->getMembership($group, $user->id());

    // Check if the subscriptions have changed. This allows us to skip saving
    // the membership entity if nothing changed.
    $bundles_value = $form_state->getValue(
      ['groups', $membership->getGroupId(), 'bundles']
    );
    // Ignore the submit button.
    unset($bundles_value['submit']);
    $subscribed_bundles = array_keys(array_filter($bundles_value));

    $original_bundles = array_map(function (array $item): string {
      return $item['bundle'];
    }, $membership->get('subscription_bundles')->getValue());

    sort($subscribed_bundles);
    sort($original_bundles);
    if ($subscribed_bundles !== $original_bundles) {
      // Bundle subscriptions have changed, update the membership.
      $membership->set('subscription_bundles', array_map(function (string $key): array {
        return array_combine(['entity_type', 'bundle'], explode('|', $key));
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
  public function submitUserFrequency(array &$form, FormStateInterface $form_state): void {
    $user = $form_state->get('#user');
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
   * AJAX callback that refreshes a group after it has been submitted.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An AJAX response.
   */
  public function reloadGroup(array &$form, FormStateInterface $form_state): AjaxResponse {
    $submitted_group_id = $this->getTriggeringElementGroupId($form_state);
    $form['groups'][$submitted_group_id]['bundles']['submit']['#value'] = $this->t('Saved!');

    // Change status of checkboxes.
    $subscription_status = [];
    $subscription_type = $form_state->getTriggeringElement()['#group_type'];
    $subscription_bundles = JoinupSubscriptionsHelper::SUBSCRIPTION_BUNDLES[$subscription_type];
    foreach ($subscription_bundles as $entity_type_id => $bundle_ids) {
      foreach ($bundle_ids as $bundle_id) {
        $key = static::getSubscriptionKey($entity_type_id, $bundle_id);
        $subscription_status[$key] = $form['groups'][$submitted_group_id]['bundles'][$key]['#checked'];
      }
    }
    $form['groups'][$submitted_group_id]['bundles']['submit']['#attributes']['data-drupal-subscriptions'] = Json::encode(array_values($subscription_status));

    $user = $form_state->get('#user');
    $form['edit-actions']['unsubscribe_all']['#access'] = $this->hasSubscriptions($user);

    return (new AjaxResponse())
      ->addCommand(new ReplaceCommand("#{$form['groups'][$submitted_group_id]['#id']}", $form['groups'][$submitted_group_id]))
      ->addCommand(new ReplaceCommand('#edit-actions', $form['edit-actions']));
  }

  /**
   * Returns the group ID for the submit button that was clicked.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the submitted form.
   *
   * @return string
   *   The group ID that corresponds to the submit button that was clicked.
   */
  protected function getTriggeringElementGroupId(FormStateInterface $form_state): string {
    // Return the group ID which is stored in the third to last parent of
    // the button: `['groups'][$group_id]['bundles']['submit']`.
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
    $form['user_subscription_settings']['field_user_frequency']['#attributes']['class'][] = 'form__subscribe-frequency';

    $form['user_subscription_settings']['field_user_frequency']['submit'] = [
      '#ajax' => [
        'callback' => '::reloadFrequency',
        'wrapper' => 'user-frequency',
      ],
      '#submit' => ['::submitUserFrequency'],
      '#type' => 'submit',
      '#extra_suggestion' => 'subscribe_save',
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

  /**
   * Returns the key used to identify the subscription bundle.
   *
   * @param string $entity_type_id
   *   The entity type of the content being subscribed to.
   * @param string $bundle_id
   *   The bundle of the content being subscribed to.
   *
   * @return string
   *   The subscription key.
   */
  protected static function getSubscriptionKey(string $entity_type_id, string $bundle_id): string {
    return implode('|', [$entity_type_id, $bundle_id]);
  }

  /**
   * Returns whether the given user has any subscriptions.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user to check.
   *
   * @return bool
   *   TRUE if the user is subscribed to at least one groups content type.
   */
  protected function hasSubscriptions(AccountInterface $user): bool {
    return (bool) $this->entityTypeManager
      ->getStorage('og_membership')
      ->getQuery()
      ->condition('uid', $user->id())
      ->exists('subscription_bundles')
      ->count()
      ->execute();
  }

}