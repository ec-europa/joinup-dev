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
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

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
        '#value' => t('Save as @transition', ['@transition' => $label]),
        '#state_id' => $state_id,
        '#state_field' => $element['#field_name'],
        '#weight' => -10,
      ];

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

}
