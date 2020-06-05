<?php

declare(strict_types = 1);

namespace Drupal\expansion_panel\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsWidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the 'expansion_panel' field widget.
 *
 * @FieldWidget(
 *   id = "expansion_panel",
 *   label = @Translation("Expansion panel"),
 *   field_types = {
 *     "entity_reference"
 *   },
 *   multiple_values = TRUE
 * )
 */
class ExpansionPanelWidget extends OptionsWidgetBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * Constructs an ExpansionPanelWidget.
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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager, EntityDisplayRepositoryInterface $entity_display_repository) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->entityTypeManager = $entity_type_manager;
    $this->entityDisplayRepository = $entity_display_repository;
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
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return ['view_mode' => FALSE];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $target_entity_type = $this->fieldDefinition->getFieldStorageDefinition()->getSetting('target_type');
    $options = array_map(function (array $view_mode_info) {
      return $view_mode_info['label'];
    }, $this->entityDisplayRepository->getViewModes($target_entity_type));

    $element['view_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('View mode to show when expanding panel'),
      '#default_value' => $this->getSetting('view_mode'),
      '#options' => $options,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    // The widget only supports entity references which target a single bundle.
    $target_bundles = $field_definition->getSetting('handler_settings')['target_bundles'] ?? [];
    if (!is_array($target_bundles)) {
      return FALSE;
    }

    if (empty($target_bundles)) {
      // If no target bundles have been specified then all are available.
      /** @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info */
      $entity_type_bundle_info = \Drupal::service('entity_type.bundle.info');
      $target_type = $field_definition->getFieldStorageDefinition()->getSetting('target_type');
      $target_bundles = array_keys($entity_type_bundle_info->getBundleInfo($target_type));
    }

    return count($target_bundles) === 1;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary[] = $this->t('View mode: @view_mode', ['@view_mode' => $this->getSetting('view_mode')]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $options = $this->getOptions($items->getEntity());
    $target_entity_type = $this->fieldDefinition->getFieldStorageDefinition()->getSetting('target_type');
    $target_entity_storage = $this->entityTypeManager->getStorage($target_entity_type);
    $target_entity_view_builder = $this->entityTypeManager->getViewBuilder($target_entity_type);
    $view_mode = $this->getSetting('view_mode');

    // Add a container that will hold the expansion panel groups as well as all
    // the standard field elements such as the field title, required flag etc.
    $element += [
      '#type' => 'container',
      '#theme_wrappers' => ['form_element'],
      '#options' => [],
      '#key_column' => $this->column,
      '#tree' => TRUE,
    ];
    $element['#element_validate'][] = [get_class($this), 'validateElement'];

    $groups = [];
    $weight = 0;
    $selected = $this->getSelectedOptions($items);
    foreach ($options as $group_title => $references) {
      $referenced_entities = $target_entity_storage->loadMultiple(array_keys($references));
      if (!empty($referenced_entities)) {
        $children = [];
        foreach ($referenced_entities as $referenced_entity) {
          $weight += 0.001;
          $key = $referenced_entity->id();
          $header = [
            '#type' => 'checkbox',
            '#title' => $referenced_entity->label(),
            '#return_value' => $key,
            '#default_value' => in_array($key, $selected) ? $key : NULL,
            // Errors should only be shown on the parent element.
            '#error_no_message' => TRUE,
            '#disabled' => FALSE,
            '#weight' => $weight,
          ];
          $content = $target_entity_view_builder->view($referenced_entity, $view_mode);
          $children[] = [
            '#theme' => 'expansion_panel',
            'header' => $header,
            'content' => $content,
          ];
        }
        $group = [
          '#title' => $group_title,
          '#type' => 'container',
          '#theme_wrappers' => ['container__expansion_panel_group'],
        ];
        $group += $children;
        $groups[] = $group;
      }
    }

    $element += $groups;

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function supportsGroups() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public static function validateElement(array $element, FormStateInterface $form_state) {
    $values = [];
    foreach (Element::children($element) as $group_key) {
      $group_element = $element[$group_key];
      foreach (Element::children($group_element) as $option_key) {
        $option_element = $group_element[$option_key];
        $value = $option_element['header']['#value'] ?? NULL;
        if (!empty($value)) {
          $values[] = [$element['#key_column'] => $value];
        }
      }
    }

    if ($element['#required'] && empty($values)) {
      $form_state->setError($element, t('@name field is required.', ['@name' => $element['#title']]));
    }

    $form_state->setValueForElement($element, $values);
  }

}
