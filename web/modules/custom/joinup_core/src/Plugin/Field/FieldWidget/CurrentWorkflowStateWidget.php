<?php

namespace Drupal\joinup_core\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\joinup\Traits\WorkflowTrait;

/**
 * Plugin implementation of the 'current_workflow_state_widget' widget.
 *
 * @FieldWidget(
 *   id = "current_workflow_state_widget",
 *   label = @Translation("Plain text"),
 *   field_types = {
 *     "current_workflow_state"
 *   }
 * )
 */
class CurrentWorkflowStateWidget extends WidgetBase {

  use WorkflowTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'title' => 'Current workflow state',
      'title_display' => 'before',
      'show_for_new_entities' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $this->getSetting('title'),
    ];
    $elements['title_display'] = [
      '#type' => 'radios',
      '#title' => $this->t('Display label'),
      '#default_value' => $this->getSetting('title_display'),
      '#options' => [
        'before' => $this->t('Label goes before the element'),
        'after' => $this->t('Label goes after the element'),
        'invisible' => $this->t('Label is there but is made invisible using CSS'),
        'attribute' => $this->t('Make it the title attribute (hover tooltip)'),
      ],
    ];
    $elements['show_for_new_entities'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show when creating a new entity'),
      '#description' => $this->t('If unchecked, the widget is shown only on forms where an existing entity is being edited.'),
      '#default_value' => $this->getSetting('show_for_new_entities'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [
      $this->t('Label: @title', [
        '@title' => $this->getSetting('title'),
      ]),
      $this->t('Display label: @title_display', [
        '@title_display' => $this->getSetting('title_display'),
      ]),
    ];

    if ($this->getSetting('show_for_new_entities')) {
      $summary[] = $this->t('Show when creating a new entity');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $entity = $items->getEntity();
    $field = $this->getEntityStateField($entity);
    $state_id = $field->getValue()['value'];

    $element['#title'] = $this->getSetting('title');
    $element['#title_display'] = $this->getSetting('title_display');
    $element['#type'] = 'item';
    $element['#element_validate'][] = [get_class($this), 'validateFormElement'];

    $element['current_workflow_state']['#type'] = 'container';
    $element['current_workflow_state']['#attributes'] = ['class' => ['current-workflow-state']];
    $element['current_workflow_state']['label'] = [
      '#plain_text' => $field->getWorkflow()->getState($state_id)->getLabel(),
    ];

    // Show the widget only when the entity is not new, or when the specific
    // setting is turned on.
    $element['#access'] = !$entity->isNew() || $this->getSetting('show_for_new_entities');

    return $element;
  }

  /**
   * Form element validation handler.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function validateFormElement(array &$element, FormStateInterface $form_state) {
    // We have nothing to validate, this data is coming from a trusted source.
    // Just set the value for the element directly.
    $form_state->setValueForElement($element['current_workflow_state'], $element['current_workflow_state']['label']['#plain_text']);
  }

}
