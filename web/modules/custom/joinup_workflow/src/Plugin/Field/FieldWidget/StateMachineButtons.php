<?php

declare(strict_types = 1);

namespace Drupal\joinup_workflow\Plugin\Field\FieldWidget;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\joinup_workflow\Event\UnchangedWorkflowStateUpdateEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Field widget showing the available workflow transition states as buttons.
 *
 * @FieldWidget(
 *   id = "state_machine_buttons",
 *   label = @Translation("State machine buttons"),
 *   field_types = {
 *     "state",
 *   },
 *   multiple_values = TRUE,
 * )
 */
class StateMachineButtons extends OptionsSelectWidget implements ContainerFactoryPluginInterface {

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a StateMachineButtons widget.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EventDispatcherInterface $event_dispatcher) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('event_dispatcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'use_transition_label' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = [];

    $element['use_transition_label'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use transition labels'),
      '#description' => $this->t('Leave unchecked to use <em>Save as</em> followed by the state label.'),
      '#default_value' => $this->getSetting('use_transition_label'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $use_transition_label = $this->getSetting('use_transition_label');
    if ($use_transition_label) {
      $summary[] = $this->t('Use transition labels');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    if ($this->getSetting('use_transition_label')) {
      $element['#options'] = $this->replaceStateLabelsWithTransitionLabels($element['#options'], $items);
    }

    // Allow modules to decide whether it is allowed to update the entity
    // without changing the workflow state. If none of the listeners forbid
    // access, we will add a submit button for the same state update.
    $state = $items->value;
    $event = new UnchangedWorkflowStateUpdateEvent($items->getEntity(), $state, $this->getDefaultSameStateUpdateLabel($state), -20);
    $this->eventDispatcher->dispatch('joinup_workflow.unchanged_workflow_state_update', $event);

    if (!$event->getAccess()->isForbidden()) {
      $element['#same_state_button'] = [
        '#value' => $event->getLabel(),
        '#weight' => $event->getWeight(),
      ];
    }

    // Merge in the cacheable metadata from the access result.
    $cacheable_metadata = CacheableMetadata::createFromRenderArray($element);
    $cacheable_metadata
      ->merge(CacheableMetadata::createFromObject($event->getAccess()))
      ->applyTo($element);

    // Pass the label settings to the process callback.
    $element['#use_transition_label'] = $this->getSetting('use_transition_label');

    // Leave the field in place for validation purposes.
    $element['#access'] = FALSE;

    // Add a process callback to add the buttons in the form actions.
    // @see \Drupal\content_moderation\Plugin\Field\FieldWidget\ModerationStateWidget::formElement()
    $element['#process'][] = [static::class, 'processActions'];
    // Let ::processActions() know the original state ID.
    $element['#original_state_id'] = $state;
    // As the '#element_validate' callbacks are running before field constraint
    // validators, we use this element validator to set the $element new value
    // in case of a transition. In this way, the state field new value will be
    // correctly validated in StateConstraintValidator::validate().
    // @see \Drupal\state_machine\Plugin\Validation\Constraint\StateConstraintValidator::validate()
    $element['#element_validate'][] = [static::class, 'setToState'];

    return $element;
  }

  /**
   * Form API process callback: add a button for each available state.
   *
   * @see \Drupal\content_moderation\Plugin\Field\FieldWidget\ModerationStateWidget::processActions()
   */
  public static function processActions($element, FormStateInterface $form_state, array &$form) {
    // We'll steal most of the button configuration from the default submit
    // button. However, NodeForm also hides that button for admins (as it adds
    // its own, too), so we have to restore it.
    $default_button = $form['actions']['submit'];
    $default_button['#access'] = TRUE;

    // Add the button to update the entity without changing the workflow state,
    // if this is allowed.
    if (!empty($element['#same_state_button'])) {
      $form['actions']['update'] = $element['#same_state_button'] + $default_button;
    }

    // Add a custom button for each state we're allowing.
    foreach ($element['#options'] as $state_id => $label) {
      if ($element['#original_state_id'] === $state_id) {
        // The 'same state' button was already added, just a few lines earlier.
        continue;
      }

      $button = [
        '#state_id' => $state_id,
        '#weight' => -10,
      ];

      // When transition labels are used, we don't need any change.
      $button['#value'] = $element['#use_transition_label']
        ? $label
        : t('Save as @transition', ['@transition' => $label]);

      $form['actions']['state_machine_' . $state_id] = $button + $default_button;
    }

    // Hide the default buttons, including the specialty ones added by
    // NodeForm.
    foreach (['publish', 'unpublish', 'submit'] as $key) {
      $form['actions'][$key]['#access'] = FALSE;
      unset($form['actions'][$key]['#dropbutton']);
    }

    return $element;
  }

  /**
   * Sets the new value of the state field in case of a transition.
   *
   * @param array $element
   *   The form element render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form API form state object.
   * @param array $complete_form
   *   The complete form render array.
   */
  public static function setToState(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $triggering_element = $form_state->getTriggeringElement();
    // This is a transition button, set the state field value.
    if (!empty($triggering_element['#state_id'])) {
      $form_state->setValueForElement($element, ['value' => $triggering_element['#state_id']]);
    }
  }

  /**
   * Replaces the state labels with the labels of the related transition.
   *
   * @param array $options
   *   The current list of options.
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   Array of default values for this field.
   *
   * @return array
   *   The update options array.
   */
  protected function replaceStateLabelsWithTransitionLabels(array $options, FieldItemListInterface $items) {
    $entity = $items->getEntity();
    $current_value = $items->value;

    // We need to get the field type class to fetch easily the related
    // workflow. Getting the option provider seems the only way.
    /** @var \Drupal\state_machine\Plugin\Field\FieldType\StateItem $state_item */
    $state_item = $this->fieldDefinition->getFieldStorageDefinition()->getOptionsProvider($this->column, $entity);
    $workflow = $state_item->getWorkflow();
    $transitions = $workflow->getAllowedTransitions($current_value, $entity);

    // Replace "to state" labels with the label associated to that transition.
    foreach ($transitions as $transition) {
      $state = $transition->getToState();
      $state_id = $state->getId();
      $options[$state_id] = $transition->getLabel();
    }

    // Sanitize again the labels.
    // @see \Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsWidgetBase::getOptions()
    array_walk_recursive($options, [$this, 'sanitizeLabel']);

    return $options;
  }

  /**
   * Returns the default label for the submit button that doesn't change state.
   *
   * @param string $state
   *   The state value.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The button label.
   */
  protected function getDefaultSameStateUpdateLabel(string $state): TranslatableMarkup {
    switch ($state) {
      case 'draft':
        return $this->t('Save as draft');

      case 'proposed':
        return $this->t('Propose');

      default:
        return $this->t('Update');
    }
  }

}
