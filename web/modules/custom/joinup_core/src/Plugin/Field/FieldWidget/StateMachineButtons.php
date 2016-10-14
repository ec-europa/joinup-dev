<?php

namespace Drupal\joinup_core\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'state_machine_buttons' widget.
 *
 * @FieldWidget(
 *   id = "state_machine_buttons",
 *   label = @Translation("State machine buttons"),
 *   field_types = {
 *     "state"
 *   },
 *   multiple_values = TRUE
 * )
 */
class StateMachineButtons extends OptionsSelectWidget {

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
      $summary[] = t('Use transition labels');
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

    // Pass the label settings to the process callback.
    $element['#use_transition_label'] = $this->getSetting('use_transition_label');

    // Leave the field in place for validation purposes.
    $element['#access'] = FALSE;

    // Add a process callback to add the buttons in the form actions.
    // @see \Drupal\content_moderation\Plugin\Field\FieldWidget\ModerationStateWidget::formElement()
    $element['#process'][] = [get_called_class(), 'processActions'];

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

    // Add a custom button for each state we're allowing.
    $options = $element['#options'];
    foreach ($options as $state_id => $label) {
      $button = [
        '#state_id' => $state_id,
        '#state_field' => $element['#field_name'],
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

    // Setup a callback to translate the button selection back into field
    // widget, so that it will get saved properly.
    $form['#entity_builders']['update_state'] = [get_called_class(), 'updateState'];

    return $element;
  }

  /**
   * Entity builder callback to set the state based on the button clicked.
   */
  public static function updateState($entity_type, EntityInterface $entity, &$form, FormStateInterface $form_state) {
    $element = $form_state->getTriggeringElement();
    if (isset($element['#state_field']) && isset($element['#state_id'])) {
      $entity->set($element['#state_field'], $element['#state_id']);
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

    // The current state is always allowed by state_machine, but a transition
    // to that state might not be available. When looping the transitions keep
    // track if a transition is available.
    $loopback_transition = FALSE;
    // Replace "to state" labels with the label associated to that transition.
    foreach ($transitions as $transition) {
      $state = $transition->getToState();
      $state_id = $state->getId();
      $options[$state_id] = $transition->getLabel();

      if ($state_id === $current_value) {
        // A transition that allows to keep the same state is available.
        $loopback_transition = TRUE;
      }
    }

    // If a transition to the current state is not available, prefix the state
    // label for better readability.
    if (!$loopback_transition) {
      $options[$current_value] = $this->t('Save as @transition', ['@transition' => $options[$current_value]]);
    }

    // Sanitize again the labels.
    // @see \Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsWidgetBase::getOptions()
    array_walk_recursive($options, [$this, 'sanitizeLabel']);

    return $options;
  }

}
